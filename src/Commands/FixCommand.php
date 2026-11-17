<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;

final class FixCommand extends ToolCommand
{
    public function name(): string
    {
        return 'fix';
    }

    public function description(): string
    {
        return 'Apply Rector refactors, then format with Pint.';
    }

    public function options(): array
    {
        return [
            '[paths...]' => 'Limit to these files or directories.',
            '--json' => 'Print a machine-readable report.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $toolchain = $this->toolchain();
        $paths = $input->arguments();

        return $this->runSteps([
            $toolchain->rector()->step($paths, false, $this->machine($input)),
            $toolchain->linter()->step($paths, false),
        ], $input, $output, $toolchain->project()->root());
    }
}
