<?php

declare(strict_types=1);

namespace Tests\Support;

use Aluisio\PhpQualityPack\Process\ProcessResult;
use Aluisio\PhpQualityPack\Process\Runner;
use Closure;

final class FakeRunner implements Runner
{
    /**
     * @var list<list<string>>
     */
    public array $commands = [];

    private Closure $responder;

    public function __construct(?Closure $responder = null)
    {
        $this->responder = $responder ?? static fn (array $command): ProcessResult => new ProcessResult($command, 0, '');
    }

    public function run(array $command, string $workingDirectory): ProcessResult
    {
        $this->commands[] = $command;

        return ($this->responder)($command);
    }

    public function stream(array $command, string $workingDirectory): int
    {
        return $this->run($command, $workingDirectory)->exitCode();
    }

    public function ran(string $needle): bool
    {
        foreach ($this->commands as $command) {
            if (strpos(implode(' ', $command), $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
