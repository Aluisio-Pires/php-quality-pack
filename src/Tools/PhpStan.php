<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

use Aluisio\PhpQualityPack\Project\Project;

final class PhpStan
{
    public const BASELINE = 'phpstan-baseline.neon';

    private const MEMORY_LIMIT = '--memory-limit=1G';

    private Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function maxLevel(): int
    {
        return ($this->project->installed()->major('phpstan/phpstan') ?? 2) >= 2 ? 10 : 9;
    }

    /**
     * @param  list<string>  $paths
     * @param  list<string>  $extra
     */
    public function step(array $paths, ?int $level, bool $machine, array $extra = []): Step
    {
        $command = array_merge($this->base(), $paths);

        if ($level !== null) {
            $command[] = '--level='.$level;
        }

        if ($machine) {
            $command[] = '--no-progress';
            $command[] = '--error-format=json';
        }

        return new Step('analyse', array_merge($command, [self::MEMORY_LIMIT], $extra), $machine ? Step::PHPSTAN_JSON : Step::TEXT);
    }

    /**
     * @param  list<string>  $extra
     */
    public function baselineStep(array $extra = []): Step
    {
        return new Step('baseline', array_merge($this->base(), [
            '--generate-baseline='.self::BASELINE,
            '--no-progress',
            self::MEMORY_LIMIT,
        ], $extra));
    }

    /**
     * @return list<string>
     */
    private function base(): array
    {
        return [PHP_BINARY, $this->project->manifest()->binDirectory().'/phpstan', 'analyse'];
    }
}
