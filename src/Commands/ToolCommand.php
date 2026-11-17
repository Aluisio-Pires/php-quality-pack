<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Command;
use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Process\Runner;
use Aluisio\PhpQualityPack\Project\Project;
use Aluisio\PhpQualityPack\Project\ProjectDetector;
use Aluisio\PhpQualityPack\Support\Json;
use Aluisio\PhpQualityPack\Tools\Report;
use Aluisio\PhpQualityPack\Tools\Step;
use Aluisio\PhpQualityPack\Tools\Toolchain;

abstract class ToolCommand implements Command
{
    protected Runner $runner;

    protected string $directory;

    private ProjectDetector $detector;

    public function __construct(Runner $runner, string $directory, ?ProjectDetector $detector = null)
    {
        $this->runner = $runner;
        $this->directory = $directory;
        $this->detector = $detector ?? new ProjectDetector;
    }

    protected function project(): Project
    {
        return $this->detector->detect($this->directory);
    }

    protected function toolchain(): Toolchain
    {
        return new Toolchain($this->project());
    }

    protected function machine(Input $input): bool
    {
        return $input->hasOption('json');
    }

    /**
     * @param  list<string>  $known
     * @return list<string>
     */
    protected function extra(Input $input, array $known): array
    {
        return $input->forwarded(array_merge(['json'], $known));
    }

    /**
     * @param  list<Step>  $steps
     */
    protected function runSteps(array $steps, Input $input, Output $output, string $root): int
    {
        if ($this->machine($input)) {
            return $this->runMachineSteps($steps, $output, $root);
        }

        $failed = [];

        foreach ($steps as $step) {
            $output->title(sprintf('› %s', $step->commandLine()));

            if ($this->runner->stream($step->command(), $root) !== 0) {
                $failed[] = $step->name();
            }

            $output->line();
        }

        if (count($steps) > 1) {
            $this->summarize($steps, $failed, $output);
        }

        return $failed === [] ? 0 : 1;
    }

    /**
     * @param  list<Step>  $steps
     */
    private function runMachineSteps(array $steps, Output $output, string $root): int
    {
        $reports = [];
        $passed = true;

        foreach ($steps as $step) {
            $report = Report::from($step, $this->runner->run($step->command(), $root), $root);
            $passed = $passed && $report['passed'] === true;
            $reports[] = $report;
        }

        $output->line(rtrim(Json::pretty(['passed' => $passed, 'steps' => $reports])));

        return $passed ? 0 : 1;
    }

    /**
     * @param  list<Step>  $steps
     * @param  list<string>  $failed
     */
    private function summarize(array $steps, array $failed, Output $output): void
    {
        foreach ($steps as $step) {
            if (in_array($step->name(), $failed, true)) {
                $output->error($step->name());

                continue;
            }

            $output->success($step->name());
        }
    }
}
