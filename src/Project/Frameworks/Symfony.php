<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project\Frameworks;

use Aluisio\PhpQualityPack\Project\ComposerManifest;
use Aluisio\PhpQualityPack\Project\InstalledPackages;
use Aluisio\PhpQualityPack\Setup\RectorSet;
use Aluisio\PhpQualityPack\Setup\Requirement;
use Rector\Symfony\Set\SymfonySetList;

final class Symfony extends BaseFramework
{
    public function name(): string
    {
        return 'symfony';
    }

    public function matches(ComposerManifest $manifest, InstalledPackages $installed): bool
    {
        return $manifest->requires('symfony/framework-bundle');
    }

    public function version(InstalledPackages $installed): ?string
    {
        return $installed->version('symfony/framework-bundle');
    }

    public function pintPreset(): string
    {
        return 'symfony';
    }

    public function phpCsFixerPreset(): string
    {
        return '@Symfony';
    }

    public function requirements(): array
    {
        return [
            new Requirement('phpstan/phpstan-symfony', '^1.0 || ^2.0', '7.2', true),
        ];
    }

    public function phpStanIncludes(InstalledPackages $installed): array
    {
        return $installed->has('phpstan/phpstan-symfony') ? ['vendor/phpstan/phpstan-symfony/extension.neon'] : [];
    }

    public function rectorSets(InstalledPackages $installed): array
    {
        return [
            new RectorSet(SymfonySetList::class, 'SYMFONY_CODE_QUALITY'),
            new RectorSet(SymfonySetList::class, 'SYMFONY_CONSTRUCTOR_INJECTION'),
        ];
    }
}
