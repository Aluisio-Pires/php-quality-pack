<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

use Aluisio\PhpQualityPack\Project\Project;

final class Pest
{
    private Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function major(): int
    {
        return $this->project->installed()->major('pestphp/pest') ?? 1;
    }

    public function supportsTypeCoverage(): bool
    {
        return $this->project->installed()->has('pestphp/pest-plugin-type-coverage');
    }

    public function supportsPresets(): bool
    {
        return $this->major() >= 3;
    }

    public function supportsArchitecture(): bool
    {
        return $this->major() >= 2;
    }

    /**
     * @param  list<string>  $arguments
     */
    public function step(array $arguments, bool $machine): Step
    {
        return new Step('test', $this->command($arguments, $machine));
    }

    /**
     * @param  list<string>  $extra
     */
    public function typeCoverageStep(float $minimum, bool $machine, array $extra = []): Step
    {
        $arguments = array_merge(['--type-coverage', '--min='.$minimum], $extra);

        return new Step('type-coverage', $this->command($arguments, $machine));
    }

    /**
     * @param  list<string>  $arguments
     * @return list<string>
     */
    private function command(array $arguments, bool $machine): array
    {
        $command = array_merge([PHP_BINARY, $this->project->manifest()->binDirectory().'/pest'], $arguments);

        if ($machine) {
            $command[] = '--colors=never';
        }

        return $command;
    }
}
