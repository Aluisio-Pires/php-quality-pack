<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Process;

final class ProcessResult
{
    /**
     * @var list<string>
     */
    private array $command;

    private int $exitCode;

    private string $output;

    private string $errorOutput;

    /**
     * @param  list<string>  $command
     */
    public function __construct(array $command, int $exitCode, string $output, string $errorOutput = '')
    {
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->errorOutput = $errorOutput;
    }

    public static function stripAnsi(string $text): string
    {
        return (string) preg_replace('/\e\[[\d;?]*[A-Za-z]|\e\][^\a]*\a/', '', $text);
    }

    /**
     * @return list<string>
     */
    public function command(): array
    {
        return $this->command;
    }

    public function exitCode(): int
    {
        return $this->exitCode;
    }

    public function successful(): bool
    {
        return $this->exitCode === 0;
    }

    public function output(): string
    {
        return $this->output;
    }

    public function errorOutput(): string
    {
        return $this->errorOutput;
    }

    public function combinedOutput(): string
    {
        return trim(self::stripAnsi($this->output."\n".$this->errorOutput));
    }
}
