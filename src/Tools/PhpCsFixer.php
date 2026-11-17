<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

use Aluisio\PhpQualityPack\Project\Project;

final class PhpCsFixer implements Linter
{
    private Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function name(): string
    {
        return 'php-cs-fixer';
    }

    public function step(array $paths, bool $check, array $extra = []): Step
    {
        $command = array_merge([PHP_BINARY, $this->project->manifest()->binDirectory().'/php-cs-fixer', 'fix'], $paths);

        if ($paths !== []) {
            $command[] = '--path-mode=intersection';
        }

        if ($check) {
            $command[] = '--dry-run';
            $command[] = '--diff';
        }

        return new Step('lint', array_merge($command, $extra));
    }
}
