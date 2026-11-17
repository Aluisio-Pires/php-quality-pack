<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Project\Project;
use Aluisio\PhpQualityPack\Setup\Calibrator;
use Aluisio\PhpQualityPack\Setup\Composer;
use Aluisio\PhpQualityPack\Setup\Configurator;
use Aluisio\PhpQualityPack\Setup\DependencyPlanner;
use Aluisio\PhpQualityPack\Setup\InstallOptions;
use Aluisio\PhpQualityPack\Setup\Requirement;

final class InstallCommand extends ToolCommand
{
    public function name(): string
    {
        return 'install';
    }

    public function description(): string
    {
        return 'Install the tools that fit this project and generate configs calibrated to its current state.';
    }

    public function options(): array
    {
        return [
            '--dry-run' => 'Show the packages that would be installed and stop.',
            '--no-composer' => 'Skip package installation; only generate configs.',
            '--force' => 'Regenerate configs that already exist.',
            '--level=<n>' => 'Use this PHPStan level instead of calibrating it.',
            '--baseline' => 'Store current PHPStan errors in a baseline (with --level).',
            '--rector=<mode>' => 'auto (default), full or gradual.',
            '--no-calibrate' => 'Skip running the tools to measure the project.',
            '--no-ai' => 'Skip publishing agent skills and the MCP server config.',
            '--no-scripts' => 'Skip adding composer scripts.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $options = InstallOptions::fromInput($input);
        $project = $this->project();

        if (! $input->hasOption('no-composer')) {
            return $this->installDependencies($project, $input, $output);
        }

        $output->title('php-quality-pack');
        $output->definitions([
            'Framework' => trim($project->framework()->name().' '.$project->framework()->version($project->installed())),
            'Target PHP' => $project->targetPhp()->toString(),
            'Runtime PHP' => $project->runtimePhp()->toString(),
        ]);
        $output->line();

        (new Configurator(new Calibrator($this->runner)))->configure($project, $options, $output);

        $output->line();
        $output->success('Done. Run `vendor/bin/pqp check` to verify the project.');

        return 0;
    }

    private function installDependencies(Project $project, Input $input, Output $output): int
    {
        $requirements = (new DependencyPlanner)->plan($project);

        $output->title(sprintf('Tools for PHP %s (%s)', $project->runtimePhp()->toString(), $project->framework()->name()));

        foreach ($requirements as $requirement) {
            $output->info(sprintf('%s %s%s', $requirement->package(), $requirement->constraint(), $requirement->optional() ? ' (optional)' : ''));
        }

        if ($requirements === []) {
            $output->info('All tools are already installed.');
        }

        if ($input->hasOption('dry-run')) {
            return 0;
        }

        $composer = new Composer($this->runner, $project->root());
        $composer->allowPlugins();

        $required = array_values(array_filter($requirements, static fn (Requirement $requirement): bool => ! $requirement->optional()));

        if (! $composer->require($required)) {
            $output->error('Composer could not install the required tools.');

            return 1;
        }

        foreach ($requirements as $requirement) {
            if ($requirement->optional() && ! $composer->require([$requirement])) {
                $output->warning(sprintf('Skipped %s: it is not compatible with the current dependencies.', $requirement->package()));
            }
        }

        $command = array_merge(
            [PHP_BINARY, dirname(__DIR__, 2).'/bin/pqp', 'install', '--no-composer'],
            $input->forwarded([]),
        );

        return $this->runner->stream($command, $project->root());
    }
}
