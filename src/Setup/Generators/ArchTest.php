<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup\Generators;

final class ArchTest
{
    public const FILE = 'tests/ArchTest.php';

    private const DEBUG_FUNCTIONS = "['dd', 'dump', 'ray', 'var_dump']";

    /**
     * @param  list<string>  $presets
     */
    public function render(int $pestMajor, array $presets): ?string
    {
        if ($pestMajor >= 3) {
            if ($presets === []) {
                return null;
            }

            $expectations = array_map(static fn (string $preset): string => "arch()->preset()->{$preset}();", $presets);

            return "<?php\n\n".implode("\n\n", $expectations)."\n";
        }

        if ($pestMajor === 2) {
            return "<?php\n\narch('debugging functions are not used')\n    ->expect(".self::DEBUG_FUNCTIONS.")\n    ->not->toBeUsed();\n";
        }

        return null;
    }
}
