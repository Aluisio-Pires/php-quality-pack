<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

use RuntimeException;

final class MissingTool extends RuntimeException
{
    public static function named(string $tool, string $package): self
    {
        return new self(sprintf('%s is not installed. Run `vendor/bin/pqp install` or `composer require --dev %s`.', $tool, $package));
    }
}
