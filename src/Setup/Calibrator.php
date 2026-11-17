<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup;

use Aluisio\PhpQualityPack\Process\ProcessResult;
use Aluisio\PhpQualityPack\Process\Runner;
use Aluisio\PhpQualityPack\Support\Json;
use Aluisio\PhpQualityPack\Tools\Step;
use Aluisio\PhpQualityPack\Tools\Toolchain;

final class Calibrator
{
    private Runner $runner;

    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
    }

    public function highestCleanPhpStanLevel(Toolchain $toolchain): ?int
    {
        $phpStan = $toolchain->phpStan();
        $isClean = (fn (int $level): bool => $this->phpStanIsClean($this->execute($toolchain, $phpStan->step([], $level, true))));

        $highest = $phpStan->maxLevel();

        if ($isClean($highest)) {
            return $highest;
        }

        if (! $isClean(0)) {
            return null;
        }

        $clean = 0;
        $dirty = $highest;

        while ($dirty - $clean > 1) {
            $middle = intdiv($clean + $dirty, 2);

            if ($isClean($middle)) {
                $clean = $middle;
            } else {
                $dirty = $middle;
            }
        }

        return $clean;
    }

    public function pendingRefactors(Toolchain $toolchain): ?int
    {
        $result = $this->execute($toolchain, $toolchain->rector()->step([], true, true));
        $data = Json::tryDecode($result->output());

        if ($data === null || ! is_array($data['totals'] ?? null)) {
            return null;
        }

        $changedFiles = $data['totals']['changed_files'] ?? 0;

        return is_int($changedFiles) ? $changedFiles : null;
    }

    public function typeCoverage(Toolchain $toolchain): ?float
    {
        $result = $this->execute($toolchain, $toolchain->pest()->typeCoverageStep(0.0, true));

        if (preg_match('/Total:?\s*([\d.]+)\s*%/i', $result->combinedOutput(), $matches) !== 1) {
            return null;
        }

        return floor((float) $matches[1] * 10) / 10;
    }

    /**
     * @phpstan-impure
     */
    public function passes(Toolchain $toolchain, Step $step): bool
    {
        return $this->execute($toolchain, $step)->successful();
    }

    /**
     * @phpstan-impure
     */
    public function execute(Toolchain $toolchain, Step $step): ProcessResult
    {
        return $this->runner->run($step->command(), $toolchain->project()->root());
    }

    private function phpStanIsClean(ProcessResult $result): bool
    {
        $data = Json::tryDecode($result->output());

        if ($data === null) {
            return false;
        }

        if (is_array($data['totals'] ?? null)) {
            return ($data['totals']['errors'] ?? null) === 0 && ($data['totals']['file_errors'] ?? null) === 0;
        }

        return $result->successful() && ($data['errors'] ?? null) === 0;
    }
}
