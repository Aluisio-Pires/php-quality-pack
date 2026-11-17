<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project\Frameworks;

use Aluisio\PhpQualityPack\Project\ComposerManifest;
use Aluisio\PhpQualityPack\Project\InstalledPackages;

abstract class BaseFramework implements Framework
{
    public function analysisPaths(string $root, ComposerManifest $manifest): array
    {
        $autoloaded = array_values(array_filter(
            $manifest->autoloadDirectories(),
            static fn (string $directory): bool => is_dir($root.'/'.$directory),
        ));

        if ($autoloaded !== []) {
            return $autoloaded;
        }

        return is_dir($root.'/src') ? ['src'] : ['.'];
    }

    public function pintPreset(): string
    {
        return 'per';
    }

    public function phpCsFixerPreset(): string
    {
        return '@PSR12';
    }

    public function requirements(): array
    {
        return [];
    }

    public function phpStanIncludes(InstalledPackages $installed): array
    {
        return [];
    }

    public function rectorSets(InstalledPackages $installed): array
    {
        return [];
    }

    public function archPresets(): array
    {
        return ['php', 'security'];
    }

    public function pestBootstrap(string $root, ComposerManifest $manifest): ?string
    {
        return null;
    }

    /**
     * @param  list<string>  $candidates
     * @return list<string>
     */
    protected function existingDirectories(string $root, array $candidates): array
    {
        return array_values(array_filter(
            $candidates,
            static fn (string $directory): bool => is_dir($root.'/'.$directory),
        ));
    }
}
