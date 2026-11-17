<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Support;

final class PhpArray
{
    /**
     * @param  mixed  $value
     */
    public static function export($value, int $depth = 0): string
    {
        if (! is_array($value)) {
            return var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth + 1);
        $isList = array_keys($value) === range(0, count($value) - 1);
        $lines = [];

        foreach ($value as $key => $item) {
            $prefix = $isList ? '' : var_export($key, true).' => ';
            $lines[] = $indent.$prefix.self::export($item, $depth + 1).',';
        }

        return "[\n".implode("\n", $lines)."\n".str_repeat('    ', $depth).']';
    }
}
