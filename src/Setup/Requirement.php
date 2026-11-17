<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup;

use Aluisio\PhpQualityPack\Project\PhpVersion;

final class Requirement
{
    private string $package;

    private string $constraint;

    private string $minimumPhp;

    private bool $optional;

    public function __construct(string $package, string $constraint, string $minimumPhp, bool $optional = false)
    {
        $this->package = $package;
        $this->constraint = $constraint;
        $this->minimumPhp = $minimumPhp;
        $this->optional = $optional;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function constraint(): string
    {
        return $this->constraint;
    }

    public function optional(): bool
    {
        return $this->optional;
    }

    public function supports(PhpVersion $php): bool
    {
        return $php->isAtLeast($this->minimumPhp);
    }

    public function toComposerArgument(): string
    {
        return $this->package.':'.$this->constraint;
    }
}
