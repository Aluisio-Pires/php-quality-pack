<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Process\AgentEnvironment;
use Aluisio\PhpQualityPack\Process\ProcessRunner;

it('captures output and exit code', function (): void {
    $result = (new ProcessRunner)->run([PHP_BINARY, '-r', 'echo "out"; fwrite(STDERR, "err"); exit(3);'], __DIR__);

    expect($result->exitCode())->toBe(3)
        ->and($result->successful())->toBeFalse()
        ->and($result->output())->toBe('out')
        ->and($result->errorOutput())->toBe('err');
});

it('hides agent variables from captured tools so their output format stays stable', function (): void {
    putenv('CLAUDECODE=1');
    putenv('CODEX_SANDBOX=seatbelt');

    $result = (new ProcessRunner)->run([PHP_BINARY, '-r', 'echo json_encode([getenv("CLAUDECODE"), getenv("CODEX_SANDBOX"), getenv("PAO_DISABLE")]);'], __DIR__);

    putenv('CLAUDECODE');
    putenv('CODEX_SANDBOX');

    expect($result->output())->toBe('[false,false,"1"]');
});

it('recognises agent variables', function (string $name, bool $expected): void {
    expect(AgentEnvironment::isAgentVariable($name))->toBe($expected);
})->with([
    ['CLAUDECODE', true],
    ['CLAUDE_CODE_ENTRYPOINT', true],
    ['CURSOR_AGENT', true],
    ['AI_AGENT', true],
    ['PATH', false],
    ['HOME', false],
]);
