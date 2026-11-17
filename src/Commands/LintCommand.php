<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;

final class LintCommand extends ToolCommand
{
    public function name(): string
    {
        return 'lint';
    }

    public function description(): string
    {
        return 'Format code with Pint (PHP-CS-Fixer on PHP 7.4).';
    }

    public function options(): array
    {
        return [
            '[paths...]' => 'Limit to these files or directories.',
            '--test' => 'Report style issues without changing files.',
            '--json' => 'Print a machine-readable report.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $toolchain = $this->toolchain();
        $step = $toolchain->linter()->step($input->arguments(), $input->hasOption('test'), $this->extra($input, ['test']));

        return $this->runSteps([$step], $input, $output, $toolchain->project()->root());
    }
}
