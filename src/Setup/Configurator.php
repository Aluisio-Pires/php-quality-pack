<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup;

use Aluisio\PhpQualityPack\Ai\AgentGuidelines;
use Aluisio\PhpQualityPack\Ai\McpRegistrar;
use Aluisio\PhpQualityPack\Ai\SkillPublisher;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Project\Conventions;
use Aluisio\PhpQualityPack\Project\ConventionScanner;
use Aluisio\PhpQualityPack\Project\Project;
use Aluisio\PhpQualityPack\Project\Settings;
use Aluisio\PhpQualityPack\Setup\Generators\ArchTest;
use Aluisio\PhpQualityPack\Setup\Generators\PhpCsFixerConfig;
use Aluisio\PhpQualityPack\Setup\Generators\PhpStanConfig;
use Aluisio\PhpQualityPack\Setup\Generators\PhpUnitConfig;
use Aluisio\PhpQualityPack\Setup\Generators\PintConfig;
use Aluisio\PhpQualityPack\Setup\Generators\RectorConfig;
use Aluisio\PhpQualityPack\Support\Filesystem;
use Aluisio\PhpQualityPack\Tools\PhpStan;
use Aluisio\PhpQualityPack\Tools\Toolchain;

final class Configurator
{
    private const PENDING_HINTS = [
        'lint' => 'Run `pqp lint` to format the code.',
        'refactor' => 'Review `pqp refactor --dry-run` and apply with `pqp refactor`.',
        'test' => 'Fix the failing tests shown by `pqp test`.',
    ];

    private Calibrator $calibrator;

    private Filesystem $filesystem;

    private ConventionScanner $scanner;

    public function __construct(Calibrator $calibrator, ?Filesystem $filesystem = null, ?ConventionScanner $scanner = null)
    {
        $this->calibrator = $calibrator;
        $this->filesystem = $filesystem ?? new Filesystem;
        $this->scanner = $scanner ?? new ConventionScanner($this->filesystem);
    }

    public function configure(Project $project, InstallOptions $options, Output $output): Settings
    {
        $toolchain = new Toolchain($project);
        $conventions = $this->scanner->scan($project->root(), $project->analysisPaths());

        $output->info(sprintf(
            'Scanned %d files: strict types %s, final classes %s, loose comparisons %s.',
            $conventions->files(),
            $conventions->usesStrictTypesEverywhere() ? 'everywhere' : 'partial',
            $conventions->usesFinalClassesEverywhere() ? 'everywhere' : 'partial',
            $conventions->avoidsLooseComparisons() ? 'none' : 'present',
        ));

        $this->configureLinter($project, $conventions, $options, $output);
        $this->configureRector($project, $toolchain, $options, $output);
        $this->configurePhpStan($project, $toolchain, $options, $output);
        $this->configurePest($project, $toolchain, $options, $output);

        $settings = $this->settings($project, $toolchain, $options, $output);
        $this->filesystem->write($project->path(Settings::FILE), $settings->toJson());
        $output->success('Wrote '.Settings::FILE.'.');

        if ($options->scripts()) {
            $this->registerScripts($project, $output);
        }

        if ($options->ai()) {
            $this->configureAi($project, $output);
        }

        return $settings;
    }

    private function configureLinter(Project $project, Conventions $conventions, InstallOptions $options, Output $output): void
    {
        if ($project->hasBinary('pint')) {
            $this->writeConfig($project, PintConfig::FILE, [PintConfig::FILE], (new PintConfig)->render($project, $conventions), $options, $output);

            return;
        }

        if ($project->hasBinary('php-cs-fixer')) {
            $this->writeConfig(
                $project,
                PhpCsFixerConfig::FILE,
                [PhpCsFixerConfig::FILE, '.php-cs-fixer.php'],
                (new PhpCsFixerConfig)->render($project, $conventions),
                $options,
                $output,
            );
        }
    }

    private function configureRector(Project $project, Toolchain $toolchain, InstallOptions $options, Output $output): void
    {
        if (! $toolchain->has('refactor') || ! $this->shouldWrite($project, [RectorConfig::FILE], $options, $output)) {
            return;
        }

        $generator = new RectorConfig;
        $mode = $options->rectorMode() === 'auto' ? RectorConfig::FULL : $options->rectorMode();
        $this->filesystem->write($project->path(RectorConfig::FILE), $generator->render($project, $mode));

        if ($options->rectorMode() === 'auto' && $options->calibrate()) {
            $output->info('Measuring pending Rector refactors…');
            $pending = $this->calibrator->pendingRefactors($toolchain);

            if ($pending !== 0) {
                $mode = RectorConfig::GRADUAL;
                $this->filesystem->write($project->path(RectorConfig::FILE), $generator->render($project, $mode));
                $output->warning(sprintf('%s files need refactoring with the full rule set. Using gradual levels.', $pending ?? 'Some'));
            }
        }

        $output->success(sprintf('Wrote %s (%s).', RectorConfig::FILE, $mode));
    }

    private function configurePhpStan(Project $project, Toolchain $toolchain, InstallOptions $options, Output $output): void
    {
        if (! $toolchain->has('analyse') || ! $this->shouldWrite($project, PhpStanConfig::FILES, $options, $output)) {
            return;
        }

        $generator = new PhpStanConfig;
        $file = $this->targetFile($project, PhpStanConfig::FILES, PhpStanConfig::FILE);
        $path = $project->path($file);
        $level = $options->level();
        $baseline = $options->baseline();

        $this->filesystem->write($path, $generator->render($project, $level ?? 'max', false));

        if ($level === null && $options->calibrate()) {
            $output->info('Calibrating PHPStan level…');
            $level = $this->calibrator->highestCleanPhpStanLevel($toolchain);
            $baseline = $level === null;
            $level ??= 0;
        }

        $levelLabel = $level === null || $level === $toolchain->phpStan()->maxLevel() ? 'max' : (string) $level;
        $this->filesystem->write($path, $generator->render($project, $levelLabel, false));

        if ($baseline) {
            $this->calibrator->execute($toolchain, $toolchain->phpStan()->baselineStep());
            $this->filesystem->write($path, $generator->render($project, $levelLabel, $this->filesystem->exists($project->path(PhpStan::BASELINE))));
            $output->warning('Existing PHPStan errors were stored in '.PhpStan::BASELINE.'.');
        }

        $output->success(sprintf('Wrote %s (level %s).', $file, $levelLabel));
    }

    private function configurePest(Project $project, Toolchain $toolchain, InstallOptions $options, Output $output): void
    {
        if (! $toolchain->has('test')) {
            return;
        }

        $pest = $toolchain->pest();
        $sourcePaths = $project->analysisPaths();

        if (! $this->exists($project, PhpUnitConfig::FILES)) {
            $this->filesystem->write($project->path(PhpUnitConfig::FILE), (new PhpUnitConfig)->render($pest->major(), $sourcePaths));
            $output->success('Wrote '.PhpUnitConfig::FILE.'.');
        }

        $bootstrap = $project->framework()->pestBootstrap($project->root(), $project->manifest());

        if ($bootstrap !== null && ! $this->exists($project, ['tests/Pest.php'])) {
            $this->filesystem->write($project->path('tests/Pest.php'), $bootstrap);
            $output->success('Wrote tests/Pest.php.');
        }

        if (! $this->exists($project, [ArchTest::FILE])) {
            $this->configureArchTest($project, $toolchain, $options, $output);
        }
    }

    private function configureArchTest(Project $project, Toolchain $toolchain, InstallOptions $options, Output $output): void
    {
        $pest = $toolchain->pest();
        $generator = new ArchTest;
        $presets = $pest->supportsPresets() ? $project->framework()->archPresets() : [];
        $contents = $generator->render($pest->major(), $presets);

        if ($contents === null) {
            return;
        }

        $path = $project->path(ArchTest::FILE);
        $this->filesystem->write($path, $contents);

        if (! $options->calibrate() || $this->calibrator->passes($toolchain, $pest->step([ArchTest::FILE], true))) {
            $output->success('Wrote '.ArchTest::FILE.'.');

            return;
        }

        $passing = [];

        foreach ($presets as $preset) {
            $this->filesystem->write($path, (string) $generator->render($pest->major(), [$preset]));

            if ($this->calibrator->passes($toolchain, $pest->step([ArchTest::FILE], true))) {
                $passing[] = $preset;
            }
        }

        if ($passing === []) {
            $this->filesystem->delete($path);
            $output->warning('Skipped '.ArchTest::FILE.': the current code does not satisfy any architecture preset.');

            return;
        }

        $this->filesystem->write($path, (string) $generator->render($pest->major(), $passing));
        $output->success(sprintf('Wrote %s (presets: %s).', ArchTest::FILE, implode(', ', $passing)));
    }

    private function settings(Project $project, Toolchain $toolchain, InstallOptions $options, Output $output): Settings
    {
        $settings = $project->settings();
        $typeCoverage = $settings->typeCoverage();

        if ($toolchain->has('test') && $toolchain->pest()->supportsTypeCoverage() && $options->calibrate()) {
            $output->info('Measuring type coverage…');
            $typeCoverage = $this->calibrator->typeCoverage($toolchain);
        }

        $settings = $settings->withTypeCoverage($typeCoverage)->withSteps(null);

        if (! $options->calibrate()) {
            return $settings;
        }

        $steps = [];

        foreach ((new Toolchain($project->withSettings($settings)))->checkSteps(false) as $step) {
            if ($step->name() === 'analyse' || $step->name() === 'type-coverage') {
                $steps[] = $step->name();

                continue;
            }

            if ($step->name() === 'test' && $project->testPaths() === []) {
                $output->warning('No tests yet, so "test" is not part of `pqp check`. Add Pest tests under tests/, then add "test" to "steps" in pqp.json.');

                continue;
            }

            $output->info(sprintf('Checking %s…', $step->name()));

            if ($this->calibrator->passes($toolchain, $step)) {
                $steps[] = $step->name();

                continue;
            }

            $output->warning(sprintf('"%s" does not pass yet, so it is not part of `pqp check`. %s Then add it to "steps" in pqp.json.', $step->name(), self::PENDING_HINTS[$step->name()] ?? ''));
        }

        return $settings->withSteps($steps);
    }

    private function registerScripts(Project $project, Output $output): void
    {
        $added = (new ComposerScripts($this->filesystem))->register($project->path('composer.json'));

        if ($added !== []) {
            $output->success('Added composer scripts: '.implode(', ', $added).'.');
        }
    }

    private function configureAi(Project $project, Output $output): void
    {
        $skills = (new SkillPublisher($this->filesystem))->publish($project->path(SkillPublisher::DEFAULT_PATH));
        $output->success(sprintf('Published %d skills to %s.', count($skills), SkillPublisher::DEFAULT_PATH));

        $file = (new McpRegistrar($this->filesystem))->register($project->root(), 'claude');
        $output->success(sprintf('Registered the "%s" MCP server in %s.', McpRegistrar::SERVER_NAME, $file));

        foreach ((new AgentGuidelines($this->filesystem))->update($project->root()) as $file) {
            $output->success('Updated '.$file.'.');
        }
    }

    /**
     * @param  list<string>  $candidates
     */
    private function writeConfig(Project $project, string $file, array $candidates, string $contents, InstallOptions $options, Output $output): void
    {
        if (! $this->shouldWrite($project, $candidates, $options, $output)) {
            return;
        }

        $file = $this->targetFile($project, $candidates, $file);
        $this->filesystem->write($project->path($file), $contents);
        $output->success('Wrote '.$file.'.');
    }

    /**
     * @param  list<string>  $candidates
     */
    private function targetFile(Project $project, array $candidates, string $default): string
    {
        foreach ($candidates as $candidate) {
            if ($this->filesystem->exists($project->path($candidate))) {
                return $candidate;
            }
        }

        return $default;
    }

    /**
     * @param  list<string>  $candidates
     */
    private function shouldWrite(Project $project, array $candidates, InstallOptions $options, Output $output): bool
    {
        foreach ($candidates as $candidate) {
            if ($this->filesystem->exists($project->path($candidate)) && ! $options->force()) {
                $output->muted(sprintf('  Kept existing %s (use --force to regenerate).', $candidate));

                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $candidates
     */
    private function exists(Project $project, array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            if ($this->filesystem->exists($project->path($candidate))) {
                return true;
            }
        }

        return false;
    }
}
