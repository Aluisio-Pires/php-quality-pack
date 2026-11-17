<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

use Aluisio\PhpQualityPack\Project\Project;

final class Rector
{
    private Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @param  list<string>  $paths
     * @param  list<string>  $extra
     */
    public function step(array $paths, bool $dryRun, bool $machine, array $extra = [], ?string $config = null): Step
    {
        $command = array_merge([PHP_BINARY, $this->project->manifest()->binDirectory().'/rector', 'process'], $paths);

        if ($dryRun) {
            $command[] = '--dry-run';
        }

        if ($config !== null) {
            $command[] = '--config='.$config;
        }

        if ($machine) {
            $command[] = '--output-format=json';
            $command[] = '--no-progress-bar';
        }

        return new Step('refactor', array_merge($command, $extra), $machine ? Step::RECTOR_JSON : Step::TEXT);
    }
}
