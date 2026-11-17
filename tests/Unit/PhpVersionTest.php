<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Project\PhpVersion;

it('finds the lowest version in a composer constraint', function (string $constraint, string $expected): void {
    expect(PhpVersion::lowestFromConstraint($constraint)->toString())->toBe($expected);
})->with([
    ['^8.2', '8.2'],
    ['^7.4 || ^8.0', '7.4'],
    ['^7.4|^8.0', '7.4'],
    ['>=8.1', '8.1'],
    ['>=7.2 <8.0', '7.2'],
    ['~8.3.0', '8.3'],
    ['8.*', '8.0'],
    ['^8', '8.0'],
    ['>=5.6', '5.6'],
    ['8.1 - 8.3', '8.1'],
]);

it('returns null when the constraint has no version', function (): void {
    expect(PhpVersion::lowestFromConstraint('*'))->toBeNull();
});

it('compares versions', function (): void {
    $version = PhpVersion::fromString('8.2.15');

    expect($version->isAtLeast('8.2'))->toBeTrue()
        ->and($version->isAtLeast('8.3'))->toBeFalse()
        ->and($version->id())->toBe(80200)
        ->and($version->compact())->toBe('82');
});

it('rejects invalid versions', function (): void {
    PhpVersion::fromString('latest');
})->throws(InvalidArgumentException::class);
