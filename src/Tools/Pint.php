<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

use Aluisio\PhpQualityPack\Project\Project;

final class Pint implements Linter
{
    private const PARALLEL_SINCE = '1.24.0';

    private Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function name(): string
    {
        return 'pint';
    }

    public function step(array $paths, bool $check, array $extra = []): Step
    {
        $command = array_merge([PHP_BINARY, $this->project->manifest()->binDirectory().'/pint'], $paths);

        if ($check) {
            $command[] = '--test';
        }

        if ($this->project->installed()->isAtLeast('laravel/pint', self::PARALLEL_SINCE)) {
            $command[] = '--parallel';
        }

        return new Step('lint', array_merge($command, $extra));
    }
}
