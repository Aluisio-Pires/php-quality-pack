<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project\Frameworks;

use Aluisio\PhpQualityPack\Project\ComposerManifest;
use Aluisio\PhpQualityPack\Project\InstalledPackages;

final class PlainPhp extends BaseFramework
{
    public function name(): string
    {
        return 'php';
    }

    public function matches(ComposerManifest $manifest, InstalledPackages $installed): bool
    {
        return true;
    }

    public function version(InstalledPackages $installed): ?string
    {
        return null;
    }
}
