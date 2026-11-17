<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Project\Settings;
use Aluisio\PhpQualityPack\Setup\ComposerScripts;
use Aluisio\PhpQualityPack\Support\Json;

it('reads and writes pqp.json', function (): void {
    $directory = temporaryProject('library');
    file_put_contents($directory.'/pqp.json', '{"steps": ["lint", "unknown", "test"], "typeCoverage": 88}');

    $settings = Settings::fromFile($directory.'/pqp.json');

    expect($settings->steps())->toBe(['lint', 'test'])
        ->and($settings->typeCoverage())->toBe(88.0)
        ->and(Json::decode($settings->withSteps(['analyse'])->toJson()))->toBe(['steps' => ['analyse'], 'typeCoverage' => 88.0]);

    removeDirectory($directory);
});

it('adds composer scripts without touching existing ones', function (): void {
    $directory = temporaryProject('laravel');
    $composer = $directory.'/composer.json';
    $manifest = Json::decode((string) file_get_contents($composer));
    $manifest['scripts'] = ['quality' => 'custom'];
    file_put_contents($composer, json_encode($manifest));

    $added = (new ComposerScripts)->register($composer);
    $scripts = Json::decode((string) file_get_contents($composer))['scripts'];

    expect($added)->toBe(['quality:fix'])
        ->and($scripts)->toBe(['quality' => 'custom', 'quality:fix' => 'pqp fix']);

    removeDirectory($directory);
});
