<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Process;

interface Runner
{
    /**
     * @param  list<string>  $command
     */
    public function run(array $command, string $workingDirectory): ProcessResult;

    /**
     * @param  list<string>  $command
     */
    public function stream(array $command, string $workingDirectory): int;
}
