<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project;

use InvalidArgumentException;

final class PhpVersion
{
    private int $major;

    private int $minor;

    public function __construct(int $major, int $minor)
    {
        $this->major = $major;
        $this->minor = $minor;
    }

    public static function runtime(): self
    {
        return new self(PHP_MAJOR_VERSION, PHP_MINOR_VERSION);
    }

    public static function fromString(string $version): self
    {
        if (preg_match('/^v?(\d+)(?:\.(\d+))?/', trim($version), $matches) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid PHP version [%s].', $version));
        }

        return new self((int) $matches[1], (int) ($matches[2] ?? 0));
    }

    public static function lowestFromConstraint(string $constraint): ?self
    {
        $terms = preg_split('/\s*(?:\|\|?|,|\s)\s*/', trim($constraint)) ?: [];
        $versions = [];

        foreach ($terms as $term) {
            if (preg_match('/^(?:[\^~]|>=?|==?)?v?(\d+)(?:\.(\d+|\*|x))?/', $term, $matches) !== 1) {
                continue;
            }

            $minor = $matches[2] ?? '0';
            $versions[] = new self((int) $matches[1], is_numeric($minor) ? (int) $minor : 0);
        }

        usort($versions, static fn (self $left, self $right): int => $left->id() <=> $right->id());

        return $versions[0] ?? null;
    }

    public function major(): int
    {
        return $this->major;
    }

    public function minor(): int
    {
        return $this->minor;
    }

    public function id(): int
    {
        return $this->major * 10000 + $this->minor * 100;
    }

    public function compact(): string
    {
        return $this->major.$this->minor;
    }

    public function isAtLeast(string $version): bool
    {
        return $this->id() >= self::fromString($version)->id();
    }

    public function toString(): string
    {
        return $this->major.'.'.$this->minor;
    }
}
