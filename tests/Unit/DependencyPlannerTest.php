<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Setup\DependencyPlanner;
use Aluisio\PhpQualityPack\Setup\Requirement;

function plannedPackages(string $directory): array
{
    return array_map(
        static fn (Requirement $requirement): string => $requirement->package(),
        (new DependencyPlanner)->plan(detectProject($directory)),
    );
}

it('skips tools that are already required or installed', function (): void {
    expect(plannedPackages(fixturePath('laravel')))->toBe(['pestphp/pest-plugin-laravel'])
        ->and(plannedPackages(fixturePath('library')))->toBe([]);
});

it('picks PHP-CS-Fixer and skips PHP 8 only tools on PHP 7.4', function (): void {
    $directory = temporaryProject('library');
    unlink($directory.'/vendor/composer/installed.json');

    expect(plannedPackages($directory))->toBe(['friendsofphp/php-cs-fixer', 'pestphp/pest']);

    removeDirectory($directory);
});

it('adds framework extensions as optional requirements', function (): void {
    $requirements = (new DependencyPlanner)->plan(detectProject(fixturePath('symfony')));
    $symfony = array_values(array_filter(
        $requirements,
        static fn (Requirement $requirement): bool => $requirement->package() === 'phpstan/phpstan-symfony',
    ));

    expect($symfony)->toHaveCount(1)
        ->and($symfony[0]->optional())->toBeTrue()
        ->and($symfony[0]->toComposerArgument())->toBe('phpstan/phpstan-symfony:^1.0 || ^2.0');
});
