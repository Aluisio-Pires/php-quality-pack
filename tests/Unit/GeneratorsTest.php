<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Project\Conventions;
use Aluisio\PhpQualityPack\Project\PhpVersion;
use Aluisio\PhpQualityPack\Setup\Generators\ArchTest;
use Aluisio\PhpQualityPack\Setup\Generators\LintRules;
use Aluisio\PhpQualityPack\Setup\Generators\PhpCsFixerConfig;
use Aluisio\PhpQualityPack\Setup\Generators\PhpStanConfig;
use Aluisio\PhpQualityPack\Setup\Generators\PhpUnitConfig;
use Aluisio\PhpQualityPack\Setup\Generators\PintConfig;
use Aluisio\PhpQualityPack\Setup\Generators\RectorConfig;
use Aluisio\PhpQualityPack\Support\Json;

it('enforces risky lint rules only when the code already follows them', function (): void {
    $strict = (new LintRules)->rules(new PhpVersion(8, 3), new Conventions(2, 2, 2, 2, 0));
    $loose = (new LintRules)->rules(new PhpVersion(8, 3), new Conventions(2, 1, 2, 1, 3));

    expect($strict)->toHaveKey('declare_strict_types')
        ->and($strict)->toHaveKey('final_class')
        ->and($strict)->toHaveKey('strict_comparison')
        ->and($loose)->not->toHaveKey('declare_strict_types')
        ->and($loose)->not->toHaveKey('final_class')
        ->and($loose)->not->toHaveKey('strict_comparison');
});

it('keeps trailing commas valid for old PHP targets', function (): void {
    $rules = (new LintRules)->rules(new PhpVersion(7, 4), new Conventions);

    expect($rules['trailing_comma_in_multiline'])->toBe(['elements' => ['arrays', 'arguments']]);
});

it('renders pint.json with the framework preset', function (): void {
    $config = Json::decode((new PintConfig)->render(detectProject(fixturePath('laravel')), new Conventions));

    expect($config['preset'])->toBe('laravel')
        ->and($config['rules'])->toHaveKey('global_namespace_import');
});

it('renders a PHP-CS-Fixer config', function (): void {
    $config = (new PhpCsFixerConfig)->render(detectProject(fixturePath('library')), new Conventions(1, 1, 1, 1, 0));

    expect($config)->toContain("'@PSR12' => true")
        ->and($config)->toContain("'declare_strict_types' => true")
        ->and($config)->toContain('->setRiskyAllowed(true)');
});

it('renders phpstan.neon.dist with extensions, level and baseline', function (): void {
    $config = (new PhpStanConfig)->render(detectProject(fixturePath('laravel')), 6, true);

    expect($config)->toBe(<<<'NEON'
includes:
    - vendor/larastan/larastan/extension.neon
    - phpstan-baseline.neon

parameters:
    level: 6
    phpVersion: 80200
    paths:
        - app
        - config
        - routes

NEON);
});

it('excludes vendor when analysing the project root', function (): void {
    $config = (new PhpStanConfig)->render(detectProject(fixturePath('legacy')), 'max', false);

    expect($config)->toContain("    excludePaths:\n        - vendor")
        ->and($config)->toContain('phpVersion: 50600');
});

it('renders a full Rector config', function (): void {
    $config = (new RectorConfig)->render(detectProject(fixturePath('library')), RectorConfig::FULL);

    expect($config)->toContain("__DIR__.'/src'")
        ->and($config)->toContain("__DIR__.'/tests'")
        ->and($config)->toContain('->withPhpSets()')
        ->and($config)->toContain('SetList::DEAD_CODE')
        ->and($config)->toContain('SetList::TYPE_DECLARATION')
        ->and($config)->not->toContain('AddOverrideAttributeToOverriddenMethodsRector');
});

it('renders a gradual Rector config for codebases with pending refactors', function (): void {
    $config = (new RectorConfig)->render(detectProject(fixturePath('legacy')), RectorConfig::GRADUAL);

    expect($config)->toContain('->withPaths([')
        ->and($config)->toContain("        __DIR__,\n")
        ->and($config)->toContain("__DIR__.'/vendor'")
        ->and($config)->toContain('->withDeadCodeLevel(0)')
        ->and($config)->not->toContain('withPhpSets');
});

it('skips the override attribute rule on PHP 8.3+', function (): void {
    $directory = temporaryProject('library');
    file_put_contents($directory.'/pqp.json', '{"php": "8.3"}');

    $config = (new RectorConfig)->render(detectProject($directory), RectorConfig::FULL);

    expect($config)->toContain('AddOverrideAttributeToOverriddenMethodsRector::class')
        ->and($config)->toContain('LevelSetList::UP_TO_PHP_83');

    removeDirectory($directory);
});

it('renders a PHPUnit config for the installed Pest version', function (): void {
    $modern = (new PhpUnitConfig)->render(3, ['src']);
    $legacy = (new PhpUnitConfig)->render(1, ['src']);

    expect($modern)->toContain('<source>')
        ->and($legacy)->toContain('<coverage processUncoveredFiles="true">')
        ->and($legacy)->toContain('<directory suffix=".php">./src</directory>');
});

it('renders architecture tests for the installed Pest version', function (): void {
    expect((new ArchTest)->render(3, ['php', 'security']))->toBe("<?php\n\narch()->preset()->php();\n\narch()->preset()->security();\n")
        ->and((new ArchTest)->render(2, []))->toContain("->expect(['dd', 'dump', 'ray', 'var_dump'])")
        ->and((new ArchTest)->render(1, ['php']))->toBeNull();
});
