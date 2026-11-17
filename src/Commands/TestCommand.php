<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;

final class TestCommand extends ToolCommand
{
    public function name(): string
    {
        return 'test';
    }

    public function description(): string
    {
        return 'Run the test suite with Pest. Unknown options are forwarded to Pest.';
    }

    public function options(): array
    {
        return [
            '[paths...]' => 'Run only these test files or directories.',
            '--type-coverage' => 'Check type coverage against the minimum in pqp.json.',
            '--json' => 'Print a machine-readable report.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $toolchain = $this->toolchain();
        $pest = $toolchain->pest();
        $machine = $this->machine($input);
        $extra = $this->extra($input, ['type-coverage']);

        if ($input->hasOption('type-coverage')) {
            $minimum = $toolchain->project()->settings()->typeCoverage() ?? 0.0;
            $step = $pest->typeCoverageStep($minimum, $machine, $extra);
        } else {
            $step = $pest->step(array_merge($input->arguments(), $extra), $machine);
        }

        return $this->runSteps([$step], $input, $output, $toolchain->project()->root());
    }
}
