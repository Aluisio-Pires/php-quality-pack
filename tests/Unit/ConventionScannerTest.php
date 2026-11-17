<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Project\ConventionScanner;

it('recognises a strict codebase', function (): void {
    $conventions = (new ConventionScanner)->scan(fixturePath('library'), ['src']);

    expect($conventions->toArray())->toMatchArray([
        'files' => 2,
        'strictTypesFiles' => 2,
        'classes' => 2,
        'finalClasses' => 2,
        'looseComparisons' => 0,
    ])
        ->and($conventions->usesStrictTypesEverywhere())->toBeTrue()
        ->and($conventions->usesFinalClassesEverywhere())->toBeTrue()
        ->and($conventions->avoidsLooseComparisons())->toBeTrue();
});

it('recognises a loose codebase', function (): void {
    $conventions = (new ConventionScanner)->scan(fixturePath('laravel'), ['app']);

    expect($conventions->toArray())->toMatchArray([
        'files' => 2,
        'strictTypesFiles' => 0,
        'classes' => 1,
        'finalClasses' => 0,
        'looseComparisons' => 1,
    ])
        ->and($conventions->usesStrictTypesEverywhere())->toBeFalse()
        ->and($conventions->usesFinalClassesEverywhere())->toBeFalse()
        ->and($conventions->avoidsLooseComparisons())->toBeFalse();
});
