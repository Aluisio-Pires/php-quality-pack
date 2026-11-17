<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup\Generators;

final class PhpUnitConfig
{
    public const FILE = 'phpunit.xml.dist';

    public const FILES = ['phpunit.xml', 'phpunit.xml.dist', 'phpunit.dist.xml'];

    /**
     * @param  list<string>  $sourcePaths
     */
    public function render(int $pestMajor, array $sourcePaths): string
    {
        $directories = implode("\n", array_map(
            static fn (string $path): string => sprintf('            <directory suffix=".php">./%s</directory>', $path === '.' ? '' : $path),
            $sourcePaths,
        ));

        $source = $pestMajor >= 2
            ? "    <source>\n        <include>\n{$directories}\n        </include>\n    </source>"
            : "    <coverage processUncoveredFiles=\"true\">\n        <include>\n{$directories}\n        </include>\n    </coverage>";

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Test Suite">
            <directory suffix="Test.php">./tests</directory>
        </testsuite>
    </testsuites>
{$source}
</phpunit>

XML;
    }
}
