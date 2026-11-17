<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup;

final class RectorSet
{
    private string $class;

    private string $constant;

    public function __construct(string $class, string $constant)
    {
        $this->class = $class;
        $this->constant = $constant;
    }

    public function className(): string
    {
        return $this->class;
    }

    public function shortClassName(): string
    {
        $position = strrpos($this->class, '\\');

        return $position === false ? $this->class : substr($this->class, $position + 1);
    }

    public function expression(): string
    {
        return $this->shortClassName().'::'.$this->constant;
    }

    public function isAvailable(): bool
    {
        return class_exists($this->class) && defined($this->class.'::'.$this->constant);
    }
}
