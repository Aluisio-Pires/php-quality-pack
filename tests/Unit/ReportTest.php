<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Process\ProcessResult;
use Aluisio\PhpQualityPack\Support\Json;
use Aluisio\PhpQualityPack\Tools\Report;
use Aluisio\PhpQualityPack\Tools\Step;

it('compacts PHPStan JSON output', function (): void {
    $output = Json::encode([
        'totals' => ['errors' => 0, 'file_errors' => 1],
        'files' => [
            '/app/src/Money.php' => [
                'errors' => 1,
                'messages' => [
                    ['message' => 'Method has no return type.', 'line' => 12, 'ignorable' => true, 'identifier' => 'missingType.return'],
                ],
            ],
        ],
        'errors' => [],
    ]);

    $report = Report::from(
        new Step('analyse', [PHP_BINARY, 'vendor/bin/phpstan', 'analyse'], Step::PHPSTAN_JSON),
        new ProcessResult([], 1, $output),
        '/app',
    );

    expect($report)->toBe([
        'step' => 'analyse',
        'command' => 'php vendor/bin/phpstan analyse',
        'passed' => false,
        'exitCode' => 1,
        'errorCount' => 1,
        'errors' => [
            ['file' => 'src/Money.php', 'line' => 12, 'message' => 'Method has no return type.', 'identifier' => 'missingType.return'],
        ],
    ]);
});

it('compacts Rector JSON output', function (): void {
    $output = Json::encode([
        'totals' => ['changed_files' => 1, 'errors' => 0],
        'file_diffs' => [
            ['file' => '/app/src/Money.php', 'diff' => '-a +b', 'applied_rectors' => ['Rector\DeadCode\Rector\RemoveUnusedVariable']],
        ],
    ]);

    $report = Report::from(new Step('refactor', [PHP_BINARY, 'vendor/bin/rector'], Step::RECTOR_JSON), new ProcessResult([], 0, $output), '/app');

    expect($report['changedFiles'])->toBe(1)
        ->and($report['changes'][0])->toBe([
            'file' => 'src/Money.php',
            'rules' => ['Rector\DeadCode\Rector\RemoveUnusedVariable'],
            'diff' => '-a +b',
        ]);
});

it('falls back to plain text and strips colors', function (): void {
    $report = Report::from(new Step('test', [PHP_BINARY, 'vendor/bin/pest']), new ProcessResult([], 0, "\e[32mPASS\e[0m Tests"), '/app');

    expect($report['output'])->toBe('PASS Tests');
});

it('truncates long output keeping the tail', function (): void {
    $text = str_repeat('a', 30000).'END';

    expect(Report::truncate($text))->toContain('…[truncated]…')
        ->and(Report::truncate($text))->toEndWith('END');
});
