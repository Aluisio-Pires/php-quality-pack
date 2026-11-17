<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Support;

use RuntimeException;

final class Json
{
    /**
     * @return array<mixed>
     */
    public static function decode(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid JSON: '.json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * @return array<mixed>|null
     */
    public static function tryDecode(string $json): ?array
    {
        $decoded = json_decode(trim($json), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  mixed  $value
     */
    public static function encode($value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

        if ($json === false) {
            throw new RuntimeException('Unable to encode JSON: '.json_last_error_msg());
        }

        return $json;
    }

    /**
     * @param  mixed  $value
     */
    public static function pretty($value): string
    {
        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

        if ($json === false) {
            throw new RuntimeException('Unable to encode JSON: '.json_last_error_msg());
        }

        return $json.PHP_EOL;
    }
}
