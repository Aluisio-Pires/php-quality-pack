<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Console;

final class Output
{
    private const COLORS = [
        'info' => '36',
        'success' => '32',
        'warning' => '33',
        'error' => '31',
        'muted' => '90',
        'title' => '1',
    ];

    /**
     * @var resource
     */
    private $stream;

    private bool $decorated;

    /**
     * @param  resource  $stream
     */
    public function __construct($stream, ?bool $decorated = null)
    {
        $this->stream = $stream;
        $this->decorated = $decorated ?? $this->supportsColors($stream);
    }

    public static function stdout(): self
    {
        return new self(STDOUT);
    }

    public function line(string $message = ''): void
    {
        fwrite($this->stream, $message.PHP_EOL);
    }

    public function title(string $message): void
    {
        $this->line($this->style('title', $message));
    }

    public function info(string $message): void
    {
        $this->line($this->style('info', '› ').$message);
    }

    public function success(string $message): void
    {
        $this->line($this->style('success', '✓ ').$message);
    }

    public function warning(string $message): void
    {
        $this->line($this->style('warning', '! ').$message);
    }

    public function error(string $message): void
    {
        $this->line($this->style('error', '✗ ').$message);
    }

    public function muted(string $message): void
    {
        $this->line($this->style('muted', $message));
    }

    /**
     * @param  array<string, string>  $rows
     */
    public function definitions(array $rows): void
    {
        $width = max(array_map('strlen', array_keys($rows)) + [0]);

        foreach ($rows as $label => $value) {
            $this->line('  '.$this->style('info', str_pad($label, $width)).'  '.$value);
        }
    }

    private function style(string $style, string $text): string
    {
        if (! $this->decorated) {
            return $text;
        }

        return sprintf("\033[%sm%s\033[0m", self::COLORS[$style], $text);
    }

    /**
     * @param  resource  $stream
     */
    private function supportsColors($stream): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        return function_exists('stream_isatty') && @stream_isatty($stream);
    }
}
