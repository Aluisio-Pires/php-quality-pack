<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project\Frameworks;

use Aluisio\PhpQualityPack\Project\ComposerManifest;
use Aluisio\PhpQualityPack\Project\InstalledPackages;
use Aluisio\PhpQualityPack\Setup\RectorSet;
use Aluisio\PhpQualityPack\Setup\Requirement;

interface Framework
{
    public function name(): string;

    public function matches(ComposerManifest $manifest, InstalledPackages $installed): bool;

    public function version(InstalledPackages $installed): ?string;

    /**
     * @return list<string>
     */
    public function analysisPaths(string $root, ComposerManifest $manifest): array;

    public function pintPreset(): string;

    public function phpCsFixerPreset(): string;

    /**
     * @return list<Requirement>
     */
    public function requirements(): array;

    /**
     * @return list<string>
     */
    public function phpStanIncludes(InstalledPackages $installed): array;

    /**
     * @return list<RectorSet>
     */
    public function rectorSets(InstalledPackages $installed): array;

    /**
     * @return list<string>
     */
    public function archPresets(): array;

    public function pestBootstrap(string $root, ComposerManifest $manifest): ?string;
}
