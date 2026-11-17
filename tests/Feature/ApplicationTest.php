<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Application;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Process\ProcessResult;
use Aluisio\PhpQualityPack\Support\Json;
use Tests\Support\FakeRunner;

function runPqp(array $arguments, ?FakeRunner $runner = null, string $fixture = 'laravel'): array
{
    $stream = fopen('php://memory', 'w+b');
    $exitCode = (new Application(fixturePath($fixture), $runner ?? new FakeRunner))->run(array_merge(['pqp'], $arguments), new Output($stream, false));
    rewind($stream);

    return [$exitCode, (string) stream_get_contents($stream)];
}

it('lists the commands', function (): void {
    [$exitCode, $output] = runPqp([]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('install')
        ->and($output)->toContain('mcp');
});

it('shows command help', function (): void {
    [, $output] = runPqp(['analyse', '--help']);

    expect($output)->toContain('pqp analyse')
        ->and($output)->toContain('--level=<n>');
});

it('prints the version', function (): void {
    expect(runPqp(['--version'])[1])->toBe('php-quality-pack '.Application::VERSION."\n");
});

it('fails on unknown commands', function (): void {
    [$exitCode, $output] = runPqp(['deploy']);

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('Unknown command [deploy]');
});

it('prints the profile as JSON', function (): void {
    $profile = Json::decode(runPqp(['detect', '--json'])[1]);

    expect($profile)->toMatchArray([
        'framework' => 'laravel',
        'frameworkVersion' => '12.4.1',
        'targetPhp' => '8.2',
        'steps' => ['lint', 'refactor', 'analyse', 'test'],
    ])
        ->and($profile['tools']['pint'])->toBe('1.32.1')
        ->and($profile['tools']['php-cs-fixer'])->toBeNull();
});

it('runs every check and reports failures', function (): void {
    $runner = new FakeRunner(static fn (array $command): ProcessResult => new ProcessResult($command, in_array('analyse', $command, true) ? 1 : 0, ''));

    [$exitCode, $output] = runPqp(['check'], $runner);

    expect($exitCode)->toBe(1)
        ->and($runner->commands)->toHaveCount(4)
        ->and($output)->toContain('✓ lint')
        ->and($output)->toContain('✗ analyse');
});

it('fixes with Rector before Pint', function (): void {
    $runner = new FakeRunner;

    runPqp(['fix', 'app'], $runner);

    expect($runner->commands[0])->toContain('process')
        ->and($runner->commands[0])->toContain('app')
        ->and($runner->commands[1][1])->toBe('vendor/bin/pint');
});

it('forwards unknown options to the tool', function (): void {
    $runner = new FakeRunner;

    runPqp(['test', 'tests/Unit', '--parallel', '--', '--compact'], $runner);

    expect(array_slice($runner->commands[0], 1))->toBe(['vendor/bin/pest', 'tests/Unit', '--parallel', '--compact']);
});

it('generates a PHPStan baseline', function (): void {
    $runner = new FakeRunner;

    runPqp(['analyse', '--baseline'], $runner);

    expect($runner->commands[0])->toContain('--generate-baseline=phpstan-baseline.neon');
});

it('upgrades code with a temporary Rector config', function (): void {
    $runner = new FakeRunner;

    [$exitCode] = runPqp(['upgrade', '--php=8.4', '--dry-run'], $runner);
    $config = array_values(array_filter($runner->commands[0], static fn (string $argument): bool => strpos($argument, '--config=') === 0));

    expect($exitCode)->toBe(0)
        ->and($config)->toHaveCount(1)
        ->and(file_exists(substr($config[0], 9)))->toBeFalse();
});

it('rejects unavailable upgrade targets', function (): void {
    [$exitCode, $output] = runPqp(['upgrade', '--php=9.9']);

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('UP_TO_PHP_99');
});

it('fails when a tool is missing', function (): void {
    [$exitCode, $output] = runPqp(['test'], null, 'symfony');

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('Pest is not installed');
});
