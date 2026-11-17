<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

use Aluisio\PhpQualityPack\Process\ProcessResult;
use Aluisio\PhpQualityPack\Support\Json;

final class Report
{
    private const HEAD_LENGTH = 4000;

    private const TAIL_LENGTH = 16000;

    /**
     * @return array<string, mixed>
     */
    public static function from(Step $step, ProcessResult $result, string $root): array
    {
        $report = [
            'step' => $step->name(),
            'command' => $step->commandLine(),
            'passed' => $result->successful(),
            'exitCode' => $result->exitCode(),
        ];

        $data = $step->format() === Step::TEXT ? null : Json::tryDecode($result->output());

        if ($data === null) {
            return $report + ['output' => self::truncate($result->combinedOutput())];
        }

        if ($step->format() === Step::PHPSTAN_JSON) {
            return $report + self::phpStan($data, $root);
        }

        return $report + self::rector($data, $root);
    }

    public static function truncate(string $text): string
    {
        if (strlen($text) <= self::HEAD_LENGTH + self::TAIL_LENGTH) {
            return $text;
        }

        return substr($text, 0, self::HEAD_LENGTH)."\n…[truncated]…\n".substr($text, -self::TAIL_LENGTH);
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, mixed>
     */
    private static function phpStan(array $data, string $root): array
    {
        $errors = [];
        $files = is_array($data['files'] ?? null) ? $data['files'] : [];

        foreach ($files as $file => $details) {
            $messages = is_array($details) && is_array($details['messages'] ?? null) ? $details['messages'] : [];

            foreach ($messages as $message) {
                if (! is_array($message)) {
                    continue;
                }

                $errors[] = array_filter([
                    'file' => self::relative((string) $file, $root),
                    'line' => $message['line'] ?? null,
                    'message' => $message['message'] ?? null,
                    'identifier' => $message['identifier'] ?? null,
                    'tip' => $message['tip'] ?? null,
                ], static fn ($value): bool => $value !== null);
            }
        }

        $generalErrors = is_array($data['errors'] ?? null) ? $data['errors'] : [];

        foreach ($generalErrors as $error) {
            $errors[] = ['message' => $error];
        }

        return ['errorCount' => count($errors), 'errors' => $errors];
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, mixed>
     */
    private static function rector(array $data, string $root): array
    {
        $changes = [];
        $diffs = is_array($data['file_diffs'] ?? null) ? $data['file_diffs'] : [];

        foreach ($diffs as $diff) {
            if (! is_array($diff)) {
                continue;
            }

            $changes[] = [
                'file' => self::relative(is_string($diff['file'] ?? null) ? $diff['file'] : '', $root),
                'rules' => $diff['applied_rectors'] ?? [],
                'diff' => $diff['diff'] ?? '',
            ];
        }

        return [
            'changedFiles' => count($changes),
            'changes' => $changes,
            'errors' => $data['errors'] ?? [],
        ];
    }

    private static function relative(string $file, string $root): string
    {
        $prefix = rtrim($root, '/').'/';

        return strpos($file, $prefix) === 0 ? substr($file, strlen($prefix)) : $file;
    }
}
