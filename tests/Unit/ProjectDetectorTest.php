<?php

declare(strict_types=1);

it('detects a Laravel application', function (): void {
    $project = detectProject(fixturePath('laravel'));

    expect($project->framework()->name())->toBe('laravel')
        ->and($project->framework()->version($project->installed()))->toBe('12.4.1')
        ->and($project->targetPhp()->toString())->toBe('8.2')
        ->and($project->analysisPaths())->toBe(['app', 'config', 'routes'])
        ->and($project->testPaths())->toBe(['tests'])
        ->and($project->refactorPaths())->toBe(['app', 'config', 'routes', 'tests']);
});

it('detects a Symfony application', function (): void {
    $project = detectProject(fixturePath('symfony'));

    expect($project->framework()->name())->toBe('symfony')
        ->and($project->targetPhp()->toString())->toBe('8.1')
        ->and($project->analysisPaths())->toBe(['src']);
});

it('detects a plain library and its platform PHP', function (): void {
    $project = detectProject(fixturePath('library'));

    expect($project->framework()->name())->toBe('php')
        ->and($project->targetPhp()->toString())->toBe('7.4')
        ->and($project->runtimePhp()->toString())->toBe('7.4')
        ->and($project->analysisPaths())->toBe(['src']);
});

it('falls back to the project root for legacy code', function (): void {
    $project = detectProject(fixturePath('legacy'));

    expect($project->targetPhp()->toString())->toBe('5.6')
        ->and($project->analysisPaths())->toBe(['.'])
        ->and($project->refactorPaths())->toBe(['.']);
});

it('finds the project root from a subdirectory', function (): void {
    expect(detectProject(fixturePath('laravel/app/Models'))->root())->toBe(fixturePath('laravel'));
});

it('applies overrides from pqp.json', function (): void {
    $directory = temporaryProject('library');
    file_put_contents($directory.'/pqp.json', '{"php": "8.3", "framework": "symfony"}');

    $project = detectProject($directory);

    expect($project->targetPhp()->toString())->toBe('8.3')
        ->and($project->framework()->name())->toBe('symfony');

    removeDirectory($directory);
});
