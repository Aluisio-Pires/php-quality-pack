<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;

final class RefactorCommand extends ToolCommand
{
    public function name(): string
    {
        return 'refactor';
    }

    public function description(): string
    {
        return 'Refactor and modernize code with Rector.';
    }

    public function options(): array
    {
        return [
            '[paths...]' => 'Limit to these files or directories.',
            '--dry-run' => 'Show the changes without applying them.',
            '--json' => 'Print a machine-readable report with diffs.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $toolchain = $this->toolchain();
        $step = $toolchain->rector()->step(
            $input->arguments(),
            $input->hasOption('dry-run'),
            $this->machine($input),
            $this->extra($input, ['dry-run']),
        );

        return $this->runSteps([$step], $input, $output, $toolchain->project()->root());
    }
}
