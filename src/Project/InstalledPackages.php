<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project;

use Aluisio\PhpQualityPack\Support\Json;

final class InstalledPackages
{
    /**
     * @var array<string, string>
     */
    private array $versions;

    /**
     * @param  array<string, string>  $versions
     */
    public function __construct(array $versions = [])
    {
        $this->versions = $versions;
    }

    public static function fromVendor(string $vendorDirectory): self
    {
        $path = $vendorDirectory.'/composer/installed.json';

        if (! is_file($path)) {
            return new self;
        }

        $data = Json::decode((string) file_get_contents($path));
        $packages = isset($data['packages']) && is_array($data['packages']) ? $data['packages'] : $data;
        $versions = [];

        foreach ($packages as $package) {
            if (is_array($package) && is_string($package['name'] ?? null) && is_string($package['version'] ?? null)) {
                $versions[$package['name']] = ltrim($package['version'], 'v');
            }
        }

        return new self($versions);
    }

    public function has(string $package): bool
    {
        return isset($this->versions[$package]);
    }

    public function version(string $package): ?string
    {
        return $this->versions[$package] ?? null;
    }

    public function major(string $package): ?int
    {
        $version = $this->version($package);

        if ($version === null || preg_match('/^(\d+)/', $version, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    public function isAtLeast(string $package, string $version): bool
    {
        $installed = $this->version($package);

        return $installed !== null
            && preg_match('/^\d+(\.\d+)*$/', $installed) === 1
            && version_compare($installed, $version, '>=');
    }
}
