<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Application;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Process\ProcessResult;
use Aluisio\PhpQualityPack\Setup\Calibrator;
use Aluisio\PhpQualityPack\Support\Json;
use Aluisio\PhpQualityPack\Tools\Toolchain;
use Tests\Support\FakeRunner;

function phpStanOutput(int $errors): string
{
    return Json::encode(['totals' => ['errors' => 0, 'file_errors' => $errors], 'files' => [], 'errors' => []]);
}

function levelOf(array $command): ?int
{
    foreach ($command as $argument) {
        if (strpos($argument, '--level=') === 0) {
            return (int) substr($argument, 8);
        }
    }

    return null;
}

function runInstall(string $directory, FakeRunner $runner, array $options = []): string
{
    $stream = fopen('php://memory', 'w+b');
    (new Application($directory, $runner))->run(array_merge(['pqp', 'install', '--no-composer'], $options), new Output($stream, false));
    rewind($stream);

    return (string) stream_get_contents($stream);
}

function calibratedRunner(int $highestCleanLevel, int $pendingRefactors = 0, array $failingSteps = []): FakeRunner
{
    return new FakeRunner(static function (array $command) use ($highestCleanLevel, $pendingRefactors, $failingSteps): ProcessResult {
        $line = implode(' ', $command);
        $level = levelOf($command);

        if ($level !== null) {
            return new ProcessResult($command, $level > $highestCleanLevel ? 1 : 0, phpStanOutput($level > $highestCleanLevel ? 3 : 0));
        }

        if (strpos($line, '--output-format=json') !== false) {
            return new ProcessResult($command, 0, Json::encode(['totals' => ['changed_files' => $pendingRefactors, 'errors' => 0]]));
        }

        if (strpos($line, '--type-coverage') !== false) {
            return new ProcessResult($command, 0, "Total: 87.46 %\n");
        }

        foreach ($failingSteps as $step) {
            if (strpos($line, (string) $step) !== false) {
                return new ProcessResult($command, 1, '');
            }
        }

        return new ProcessResult($command, 0, '');
    });
}

beforeEach(function (): void {
    $this->directory = temporaryProject('laravel');
});

afterEach(function (): void {
    removeDirectory($this->directory);
});

it('finds the highest clean PHPStan level with a binary search', function (): void {
    $runner = calibratedRunner(6);

    $level = (new Calibrator($runner))->highestCleanPhpStanLevel(new Toolchain(detectProject($this->directory)));

    expect($level)->toBe(6)
        ->and(array_map('levelOf', $runner->commands))->toBe([10, 0, 5, 7, 6]);
});

it('returns null when even level 0 fails', function (): void {
    expect((new Calibrator(calibratedRunner(-1)))->highestCleanPhpStanLevel(new Toolchain(detectProject($this->directory))))->toBeNull();
});

it('generates configs calibrated to the project', function (): void {
    $output = runInstall($this->directory, calibratedRunner(4));

    expect(file_get_contents($this->directory.'/phpstan.neon.dist'))->toContain('level: 4')
        ->and(file_get_contents($this->directory.'/rector.php'))->toContain('SetList::DEAD_CODE')
        ->and(Json::decode((string) file_get_contents($this->directory.'/pint.json'))['preset'])->toBe('laravel')
        ->and(file_get_contents($this->directory.'/tests/Pest.php'))->toContain("TestCase::class)->in('Feature');")
        ->and(file_get_contents($this->directory.'/tests/ArchTest.php'))->toContain('arch()->preset()->laravel();')
        ->and(file_exists($this->directory.'/phpunit.xml.dist'))->toBeTrue()
        ->and(Json::decode((string) file_get_contents($this->directory.'/pqp.json')))->toBe([
            'steps' => ['lint', 'refactor', 'analyse', 'test', 'type-coverage'],
            'typeCoverage' => 87.4,
        ])
        ->and($output)->toContain('Wrote phpstan.neon.dist (level 4)');
});

it('keeps existing configs unless forced', function (): void {
    file_put_contents($this->directory.'/phpstan.neon', "parameters:\n    level: 1\n");

    runInstall($this->directory, calibratedRunner(10));
    expect(file_get_contents($this->directory.'/phpstan.neon'))->toBe("parameters:\n    level: 1\n");

    runInstall($this->directory, calibratedRunner(10), ['--force']);
    expect(file_get_contents($this->directory.'/phpstan.neon'))->toContain('level: max')
        ->and(file_exists($this->directory.'/phpstan.neon.dist'))->toBeFalse();
});

it('falls back to a baseline when level 0 fails', function (): void {
    $runner = calibratedRunner(-1);

    runInstall($this->directory, $runner);

    expect(file_get_contents($this->directory.'/phpstan.neon.dist'))->toContain('level: 0')
        ->and($runner->ran('--generate-baseline=phpstan-baseline.neon'))->toBeTrue();
});

it('uses gradual Rector levels when refactors are pending', function (): void {
    runInstall($this->directory, calibratedRunner(10, 12));

    expect(file_get_contents($this->directory.'/rector.php'))->toContain('->withTypeCoverageLevel(0)');
});

it('leaves failing steps out of pqp check', function (): void {
    runInstall($this->directory, calibratedRunner(10, 0, ['pint']));

    expect(Json::decode((string) file_get_contents($this->directory.'/pqp.json'))['steps'])->toBe(['refactor', 'analyse', 'test', 'type-coverage']);
});

it('keeps only the architecture presets the code satisfies', function (): void {
    $archTest = $this->directory.'/tests/ArchTest.php';
    $runner = new FakeRunner(static function (array $command) use ($archTest): ProcessResult {
        $failsArchitecture = in_array('tests/ArchTest.php', $command, true)
            && strpos((string) file_get_contents($archTest), 'laravel()') !== false;

        return new ProcessResult($command, $failsArchitecture ? 1 : 0, phpStanOutput(0));
    });

    runInstall($this->directory, $runner, ['--level=5', '--rector=full']);

    expect(file_get_contents($archTest))->toBe("<?php\n\narch()->preset()->php();\n\narch()->preset()->security();\n");
});

it('publishes the AI integration and composer scripts', function (): void {
    runInstall($this->directory, calibratedRunner(10));

    $composer = Json::decode((string) file_get_contents($this->directory.'/composer.json'));

    expect(file_exists($this->directory.'/.claude/skills/pqp/SKILL.md'))->toBeTrue()
        ->and(file_exists($this->directory.'/.mcp.json'))->toBeTrue()
        ->and($composer['scripts'])->toBe(['quality' => 'pqp check', 'quality:fix' => 'pqp fix']);
});

it('can skip calibration and the AI integration', function (): void {
    $runner = new FakeRunner;

    runInstall($this->directory, $runner, ['--no-calibrate', '--no-ai', '--no-scripts']);

    expect($runner->commands)->toBe([])
        ->and(file_get_contents($this->directory.'/phpstan.neon.dist'))->toContain('level: max')
        ->and(file_exists($this->directory.'/.mcp.json'))->toBeFalse()
        ->and(Json::decode((string) file_get_contents($this->directory.'/pqp.json')))->toBe([]);
});
