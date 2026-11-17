<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project\Frameworks;

use Aluisio\PhpQualityPack\Project\ComposerManifest;
use Aluisio\PhpQualityPack\Project\InstalledPackages;
use Aluisio\PhpQualityPack\Setup\RectorSet;
use Aluisio\PhpQualityPack\Setup\Requirement;

final class Laravel extends BaseFramework
{
    private const PACKAGES = ['laravel/framework', 'illuminate/support', 'orchestra/testbench'];

    private const LARASTAN_EXTENSIONS = [
        'larastan/larastan' => 'vendor/larastan/larastan/extension.neon',
        'nunomaduro/larastan' => 'vendor/nunomaduro/larastan/extension.neon',
    ];

    public function name(): string
    {
        return 'laravel';
    }

    public function matches(ComposerManifest $manifest, InstalledPackages $installed): bool
    {
        foreach (self::PACKAGES as $package) {
            if ($manifest->requires($package)) {
                return true;
            }
        }

        return false;
    }

    public function version(InstalledPackages $installed): ?string
    {
        return $installed->version('laravel/framework') ?? $installed->version('illuminate/support');
    }

    public function analysisPaths(string $root, ComposerManifest $manifest): array
    {
        if (! is_dir($root.'/app')) {
            return parent::analysisPaths($root, $manifest);
        }

        return $this->existingDirectories($root, ['app', 'config', 'database', 'routes']);
    }

    public function pintPreset(): string
    {
        return 'laravel';
    }

    public function requirements(): array
    {
        return [
            new Requirement('larastan/larastan', '^2.0 || ^3.0', '8.0', true),
            new Requirement('driftingly/rector-laravel', '^1.0 || ^2.0', '7.2', true),
            new Requirement('pestphp/pest-plugin-laravel', '^1.0 || ^2.0 || ^3.0 || ^4.0 || ^5.0', '7.3', true),
        ];
    }

    public function phpStanIncludes(InstalledPackages $installed): array
    {
        foreach (self::LARASTAN_EXTENSIONS as $package => $extension) {
            if ($installed->has($package)) {
                return [$extension];
            }
        }

        return [];
    }

    public function rectorSets(InstalledPackages $installed): array
    {
        if (! $installed->has('driftingly/rector-laravel')) {
            return [];
        }

        $sets = [
            new RectorSet('RectorLaravel\Set\LaravelSetList', 'LARAVEL_CODE_QUALITY'),
            new RectorSet('RectorLaravel\Set\LaravelSetList', 'LARAVEL_COLLECTION'),
        ];

        $major = $installed->major('laravel/framework') ?? $installed->major('illuminate/support');

        if ($major !== null) {
            $sets[] = new RectorSet('RectorLaravel\Set\LaravelLevelSetList', 'UP_TO_LARAVEL_'.$major.'0');
        }

        return $sets;
    }

    public function archPresets(): array
    {
        return ['php', 'security', 'laravel'];
    }

    public function pestBootstrap(string $root, ComposerManifest $manifest): ?string
    {
        $namespace = $manifest->autoloadDevNamespace('tests');

        if ($namespace === null || ! is_file($root.'/tests/TestCase.php')) {
            return null;
        }

        return sprintf("<?php\n\nuses(%sTestCase::class)->in('Feature');\n", $namespace);
    }
}
