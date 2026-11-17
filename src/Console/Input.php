<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Console;

final class Input
{
    private string $command;

    /**
     * @var list<string>
     */
    private array $arguments;

    /**
     * @var array<string, string|true>
     */
    private array $options;

    /**
     * @var list<string>
     */
    private array $passthrough;

    /**
     * @param  list<string>  $arguments
     * @param  array<string, string|true>  $options
     * @param  list<string>  $passthrough
     */
    public function __construct(string $command, array $arguments = [], array $options = [], array $passthrough = [])
    {
        $this->command = $command;
        $this->arguments = $arguments;
        $this->options = $options;
        $this->passthrough = $passthrough;
    }

    /**
     * @param  list<string>  $argv
     */
    public static function fromArgv(array $argv): self
    {
        $tokens = array_slice($argv, 1);
        $command = '';
        $arguments = [];
        $options = [];
        $passthrough = [];

        while ($tokens !== []) {
            $token = array_shift($tokens);

            if ($token === '--') {
                $passthrough = $tokens;

                break;
            }

            if (strpos($token, '-') === 0 && $token !== '-') {
                [$name, $value] = self::parseOption($token);
                $options[$name] = $value;

                continue;
            }

            if ($command === '') {
                $command = $token;

                continue;
            }

            $arguments[] = $token;
        }

        return new self($command, $arguments, $options, $passthrough);
    }

    public function command(): string
    {
        return $this->command;
    }

    /**
     * @return list<string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function option(string $name): ?string
    {
        $value = $this->options[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<string, string|true>
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @return list<string>
     */
    public function passthrough(): array
    {
        return $this->passthrough;
    }

    /**
     * @param  list<string>  $known
     * @return list<string>
     */
    public function forwarded(array $known): array
    {
        $forwarded = [];

        foreach ($this->options as $name => $value) {
            if (in_array($name, $known, true)) {
                continue;
            }

            $prefix = strlen($name) === 1 ? '-' : '--';
            $forwarded[] = $value === true ? $prefix.$name : $prefix.$name.'='.$value;
        }

        return array_merge($forwarded, $this->passthrough);
    }

    /**
     * @return array{0: string, 1: string|true}
     */
    private static function parseOption(string $token): array
    {
        $option = ltrim($token, '-');
        $separator = strpos($option, '=');

        if ($separator === false) {
            return [$option, true];
        }

        return [substr($option, 0, $separator), substr($option, $separator + 1)];
    }
}
