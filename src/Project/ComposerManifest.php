<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project;

use Aluisio\PhpQualityPack\Support\Json;

final class ComposerManifest
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromFile(string $path): self
    {
        if (! is_file($path)) {
            return new self([]);
        }

        /** @var array<string, mixed> $data */
        $data = Json::decode((string) file_get_contents($path));

        return new self($data);
    }

    public function name(): string
    {
        return $this->string('name');
    }

    public function requires(string $package): bool
    {
        return array_key_exists($package, $this->dependencies());
    }

    public function constraint(string $package): ?string
    {
        $constraint = $this->dependencies()[$package] ?? null;

        return is_string($constraint) ? $constraint : null;
    }

    public function phpConstraint(): ?string
    {
        $require = $this->section('require');
        $constraint = $require['php'] ?? null;

        return is_string($constraint) ? $constraint : null;
    }

    public function platformPhp(): ?string
    {
        $config = $this->section('config');
        $platform = is_array($config['platform'] ?? null) ? $config['platform'] : [];
        $php = $platform['php'] ?? null;

        return is_string($php) ? $php : null;
    }

    public function binDirectory(): string
    {
        $config = $this->section('config');
        $binDirectory = $config['bin-dir'] ?? null;

        return is_string($binDirectory) ? rtrim($binDirectory, '/') : 'vendor/bin';
    }

    public function vendorDirectory(): string
    {
        $config = $this->section('config');
        $vendorDirectory = $config['vendor-dir'] ?? null;

        return is_string($vendorDirectory) ? rtrim($vendorDirectory, '/') : 'vendor';
    }

    /**
     * @return list<string>
     */
    public function autoloadDirectories(): array
    {
        return $this->directoriesFrom($this->section('autoload'));
    }

    /**
     * @return list<string>
     */
    public function autoloadDevDirectories(): array
    {
        return $this->directoriesFrom($this->section('autoload-dev'));
    }

    public function autoloadDevNamespace(string $directory): ?string
    {
        $autoload = $this->section('autoload-dev');
        $mappings = is_array($autoload['psr-4'] ?? null) ? $autoload['psr-4'] : [];

        foreach ($mappings as $namespace => $paths) {
            foreach ((array) $paths as $path) {
                if (is_string($namespace) && is_string($path) && trim($path, '/') === $directory) {
                    return $namespace;
                }
            }
        }

        return null;
    }

    /**
     * @return array<mixed>
     */
    private function dependencies(): array
    {
        return array_merge($this->section('require'), $this->section('require-dev'));
    }

    /**
     * @param  array<mixed>  $autoload
     * @return list<string>
     */
    private function directoriesFrom(array $autoload): array
    {
        $directories = [];

        foreach (['psr-4', 'psr-0', 'classmap'] as $type) {
            $mappings = is_array($autoload[$type] ?? null) ? $autoload[$type] : [];

            array_walk_recursive($mappings, static function ($path) use (&$directories): void {
                if (is_string($path)) {
                    $directories[] = trim($path, '/') === '' ? '.' : trim($path, '/');
                }
            });
        }

        return array_values(array_unique($directories));
    }

    /**
     * @return array<mixed>
     */
    private function section(string $key): array
    {
        $section = $this->data[$key] ?? [];

        return is_array($section) ? $section : [];
    }

    private function string(string $key): string
    {
        $value = $this->data[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
