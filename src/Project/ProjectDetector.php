<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project;

use Aluisio\PhpQualityPack\Project\Frameworks\Framework;
use Aluisio\PhpQualityPack\Project\Frameworks\Laravel;
use Aluisio\PhpQualityPack\Project\Frameworks\PlainPhp;
use Aluisio\PhpQualityPack\Project\Frameworks\Symfony;

final class ProjectDetector
{
    /**
     * @var list<Framework>
     */
    private array $frameworks;

    /**
     * @param  list<Framework>|null  $frameworks
     */
    public function __construct(?array $frameworks = null)
    {
        $this->frameworks = $frameworks ?? [new Laravel, new Symfony, new PlainPhp];
    }

    public function detect(string $directory): Project
    {
        $root = $this->findRoot($directory);
        $manifest = ComposerManifest::fromFile($root.'/composer.json');
        $installed = InstalledPackages::fromVendor($root.'/'.$manifest->vendorDirectory());
        $settings = Settings::fromFile($root.'/'.Settings::FILE);

        return new Project(
            $root,
            $manifest,
            $installed,
            $this->framework($manifest, $installed, $settings),
            $this->targetPhp($manifest, $settings),
            $this->runtimePhp($manifest),
            $settings,
        );
    }

    private function findRoot(string $directory): string
    {
        $current = rtrim($directory, '/\\') ?: DIRECTORY_SEPARATOR;

        while (true) {
            if (is_file($current.'/composer.json')) {
                return $current;
            }

            $parent = dirname($current);

            if ($parent === $current) {
                return rtrim($directory, '/\\') ?: DIRECTORY_SEPARATOR;
            }

            $current = $parent;
        }
    }

    private function framework(ComposerManifest $manifest, InstalledPackages $installed, Settings $settings): Framework
    {
        foreach ($this->frameworks as $framework) {
            if ($settings->framework() !== null) {
                if ($framework->name() === $settings->framework()) {
                    return $framework;
                }

                continue;
            }

            if ($framework->matches($manifest, $installed)) {
                return $framework;
            }
        }

        return new PlainPhp;
    }

    private function targetPhp(ComposerManifest $manifest, Settings $settings): PhpVersion
    {
        if ($settings->php() !== null) {
            return PhpVersion::fromString($settings->php());
        }

        $constraint = $manifest->phpConstraint();
        $version = $constraint === null ? null : PhpVersion::lowestFromConstraint($constraint);

        return $version ?? $this->runtimePhp($manifest);
    }

    private function runtimePhp(ComposerManifest $manifest): PhpVersion
    {
        $platform = $manifest->platformPhp();

        return $platform === null ? PhpVersion::runtime() : PhpVersion::fromString($platform);
    }
}
