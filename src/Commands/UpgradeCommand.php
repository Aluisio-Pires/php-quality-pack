<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Project\PhpVersion;
use Aluisio\PhpQualityPack\Setup\Generators\RectorConfig;
use Aluisio\PhpQualityPack\Setup\RectorSet;
use Aluisio\PhpQualityPack\Support\Filesystem;
use Rector\Set\ValueObject\LevelSetList;

final class UpgradeCommand extends ToolCommand
{
    public function name(): string
    {
        return 'upgrade';
    }

    public function description(): string
    {
        return 'Upgrade code to a newer PHP or Laravel version with Rector.';
    }

    public function options(): array
    {
        return [
            '--php=<version>' => 'Target PHP version, e.g. 8.3.',
            '--laravel=<major>' => 'Target Laravel major version, e.g. 12 (requires driftingly/rector-laravel).',
            '--dry-run' => 'Show the changes without applying them.',
            '--json' => 'Print a machine-readable report with diffs.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $sets = $this->sets($input);

        if ($sets === []) {
            $output->error('Provide --php=<version> and/or --laravel=<major>.');

            return 1;
        }

        foreach ($sets as $set) {
            if (! $set->isAvailable()) {
                $output->error(sprintf('The Rector set %s is not available. Check the version or install the matching Rector package.', $set->expression()));

                return 1;
            }
        }

        $toolchain = $this->toolchain();
        $project = $toolchain->project();
        $filesystem = new Filesystem;
        $config = sys_get_temp_dir().'/pqp-upgrade-'.hash('crc32b', $project->root()).'.php';

        $filesystem->write($config, (new RectorConfig)->renderUpgrade($project, $sets));

        try {
            $step = $toolchain->rector()->step(
                [],
                $input->hasOption('dry-run'),
                $this->machine($input),
                $this->extra($input, ['php', 'laravel', 'dry-run']),
                $config,
            );

            $exitCode = $this->runSteps([$step], $input, $output, $project->root());
        } finally {
            $filesystem->delete($config);
        }

        if (! $this->machine($input) && ! $input->hasOption('dry-run') && $input->option('php') !== null) {
            $output->info(sprintf('Update "require.php" in composer.json to match PHP %s.', $input->option('php')));
        }

        return $exitCode;
    }

    /**
     * @return list<RectorSet>
     */
    private function sets(Input $input): array
    {
        $sets = [];
        $php = $input->option('php');
        $laravel = $input->option('laravel');

        if ($php !== null) {
            $sets[] = new RectorSet(LevelSetList::class, 'UP_TO_PHP_'.PhpVersion::fromString($php)->compact());
        }

        if ($laravel !== null) {
            $sets[] = new RectorSet('RectorLaravel\Set\LaravelLevelSetList', 'UP_TO_LARAVEL_'.(int) $laravel.'0');
        }

        return $sets;
    }
}
