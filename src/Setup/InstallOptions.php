<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Setup\Generators\RectorConfig;
use InvalidArgumentException;

final class InstallOptions
{
    private bool $force;

    private bool $calibrate;

    private ?int $level;

    private bool $baseline;

    private string $rectorMode;

    private bool $ai;

    private bool $scripts;

    public function __construct(
        bool $force = false,
        bool $calibrate = true,
        ?int $level = null,
        bool $baseline = false,
        string $rectorMode = 'auto',
        bool $ai = true,
        bool $scripts = true
    ) {
        $this->force = $force;
        $this->calibrate = $calibrate;
        $this->level = $level;
        $this->baseline = $baseline;
        $this->rectorMode = $rectorMode;
        $this->ai = $ai;
        $this->scripts = $scripts;
    }

    public static function fromInput(Input $input): self
    {
        $level = $input->option('level');
        $rectorMode = $input->option('rector') ?? 'auto';

        if ($level !== null && ! ctype_digit($level)) {
            throw new InvalidArgumentException('The --level option must be an integer.');
        }

        if (! in_array($rectorMode, ['auto', RectorConfig::FULL, RectorConfig::GRADUAL], true)) {
            throw new InvalidArgumentException('The --rector option must be auto, full or gradual.');
        }

        return new self(
            $input->hasOption('force'),
            ! $input->hasOption('no-calibrate'),
            $level === null ? null : (int) $level,
            $input->hasOption('baseline'),
            $rectorMode,
            ! $input->hasOption('no-ai'),
            ! $input->hasOption('no-scripts'),
        );
    }

    public function force(): bool
    {
        return $this->force;
    }

    public function calibrate(): bool
    {
        return $this->calibrate;
    }

    public function level(): ?int
    {
        return $this->level;
    }

    public function baseline(): bool
    {
        return $this->baseline;
    }

    public function rectorMode(): string
    {
        return $this->rectorMode;
    }

    public function ai(): bool
    {
        return $this->ai;
    }

    public function scripts(): bool
    {
        return $this->scripts;
    }
}
