<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Tools\MissingTool;
use Aluisio\PhpQualityPack\Tools\Step;
use Aluisio\PhpQualityPack\Tools\Toolchain;

function stepNames(array $steps): array
{
    return array_map(static fn (Step $step): string => $step->name(), $steps);
}

it('builds check steps for the installed tools', function (): void {
    $steps = (new Toolchain(detectProject(fixturePath('laravel'))))->checkSteps(false);

    expect(stepNames($steps))->toBe(['lint', 'refactor', 'analyse', 'test'])
        ->and($steps[0]->commandLine())->toBe('php vendor/bin/pint --test --parallel')
        ->and($steps[1]->commandLine())->toBe('php vendor/bin/rector process --dry-run')
        ->and($steps[2]->commandLine())->toBe('php vendor/bin/phpstan analyse --memory-limit=1G');
});

it('uses machine readable formats', function (): void {
    $steps = (new Toolchain(detectProject(fixturePath('laravel'))))->checkSteps(true);

    expect($steps[1]->format())->toBe(Step::RECTOR_JSON)
        ->and($steps[1]->commandLine())->toContain('--output-format=json')
        ->and($steps[2]->format())->toBe(Step::PHPSTAN_JSON)
        ->and($steps[2]->commandLine())->toContain('--error-format=json')
        ->and($steps[3]->commandLine())->toContain('--colors=never');
});

it('respects the steps and type coverage in pqp.json', function (): void {
    $directory = temporaryProject('laravel');
    file_put_contents($directory.'/pqp.json', '{"steps": ["analyse", "type-coverage"], "typeCoverage": 91.5}');

    $steps = (new Toolchain(detectProject($directory)))->checkSteps(false);

    expect(stepNames($steps))->toBe(['analyse', 'type-coverage'])
        ->and($steps[1]->commandLine())->toBe('php vendor/bin/pest --type-coverage --min=91.5');

    removeDirectory($directory);
});

it('falls back to PHP-CS-Fixer', function (): void {
    $step = (new Toolchain(detectProject(fixturePath('library'))))->linter()->step(['src'], true);

    expect($step->commandLine())->toBe('php vendor/bin/php-cs-fixer fix src --path-mode=intersection --dry-run --diff');
});

it('reports missing tools with an install hint', function (): void {
    (new Toolchain(detectProject(fixturePath('symfony'))))->pest();
})->throws(MissingTool::class, 'Pest is not installed. Run `vendor/bin/pqp install`');

it('uses the PHPStan max level of the installed major version', function (): void {
    expect((new Toolchain(detectProject(fixturePath('laravel'))))->phpStan()->maxLevel())->toBe(10)
        ->and((new Toolchain(detectProject(fixturePath('symfony'))))->phpStan()->maxLevel())->toBe(9);
});
