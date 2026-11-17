<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;

final class CheckCommand extends ToolCommand
{
    public function name(): string
    {
        return 'check';
    }

    public function description(): string
    {
        return 'Run every quality gate without changing files: lint, refactor, analyse, test, type coverage.';
    }

    public function options(): array
    {
        return [
            '--json' => 'Print a machine-readable report.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $toolchain = $this->toolchain();
        $steps = $toolchain->checkSteps($this->machine($input));

        if ($steps === []) {
            $output->warning('No quality steps are enabled. Run `vendor/bin/pqp install`.');

            return 1;
        }

        return $this->runSteps($steps, $input, $output, $toolchain->project()->root());
    }
}
