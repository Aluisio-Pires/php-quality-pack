<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;

final class AnalyseCommand extends ToolCommand
{
    public function name(): string
    {
        return 'analyse';
    }

    public function description(): string
    {
        return 'Find type errors and bugs with PHPStan.';
    }

    public function options(): array
    {
        return [
            '[paths...]' => 'Limit to these files or directories.',
            '--level=<n>' => 'Override the configured level for this run.',
            '--baseline' => 'Regenerate phpstan-baseline.neon with the current errors.',
            '--json' => 'Print a machine-readable report.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $toolchain = $this->toolchain();
        $phpStan = $toolchain->phpStan();
        $extra = $this->extra($input, ['level', 'baseline']);

        if ($input->hasOption('baseline')) {
            return $this->runSteps([$phpStan->baselineStep($extra)], $input, $output, $toolchain->project()->root());
        }

        $level = $input->option('level');
        $step = $phpStan->step($input->arguments(), $level === null ? null : (int) $level, $this->machine($input), $extra);

        return $this->runSteps([$step], $input, $output, $toolchain->project()->root());
    }
}
