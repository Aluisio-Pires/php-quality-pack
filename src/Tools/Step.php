<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

final class Step
{
    public const TEXT = 'text';

    public const PHPSTAN_JSON = 'phpstan-json';

    public const RECTOR_JSON = 'rector-json';

    private string $name;

    /**
     * @var list<string>
     */
    private array $command;

    private string $format;

    /**
     * @param  list<string>  $command
     */
    public function __construct(string $name, array $command, string $format = self::TEXT)
    {
        $this->name = $name;
        $this->command = $command;
        $this->format = $format;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return list<string>
     */
    public function command(): array
    {
        return $this->command;
    }

    public function format(): string
    {
        return $this->format;
    }

    public function commandLine(): string
    {
        $arguments = $this->command;
        $arguments[0] = 'php';

        return implode(' ', array_map(static fn (string $argument): string => preg_match('/^[\w\/.:=,@%+-]+$/', $argument) === 1 ? $argument : escapeshellarg($argument), $arguments));
    }
}
