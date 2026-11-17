<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project;

use Aluisio\PhpQualityPack\Project\Frameworks\Framework;

final class Project
{
    private string $root;

    private ComposerManifest $manifest;

    private InstalledPackages $installed;

    private Framework $framework;

    private PhpVersion $targetPhp;

    private PhpVersion $runtimePhp;

    private Settings $settings;

    public function __construct(
        string $root,
        ComposerManifest $manifest,
        InstalledPackages $installed,
        Framework $framework,
        PhpVersion $targetPhp,
        PhpVersion $runtimePhp,
        Settings $settings
    ) {
        $this->root = $root;
        $this->manifest = $manifest;
        $this->installed = $installed;
        $this->framework = $framework;
        $this->targetPhp = $targetPhp;
        $this->runtimePhp = $runtimePhp;
        $this->settings = $settings;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function path(string $relativePath = ''): string
    {
        return $relativePath === '' ? $this->root : $this->root.'/'.ltrim($relativePath, '/');
    }

    public function manifest(): ComposerManifest
    {
        return $this->manifest;
    }

    public function installed(): InstalledPackages
    {
        return $this->installed;
    }

    public function framework(): Framework
    {
        return $this->framework;
    }

    public function targetPhp(): PhpVersion
    {
        return $this->targetPhp;
    }

    public function runtimePhp(): PhpVersion
    {
        return $this->runtimePhp;
    }

    public function settings(): Settings
    {
        return $this->settings;
    }

    public function withSettings(Settings $settings): self
    {
        $project = clone $this;
        $project->settings = $settings;

        return $project;
    }

    public function binary(string $name): string
    {
        return $this->path($this->manifest->binDirectory().'/'.$name);
    }

    public function hasBinary(string $name): bool
    {
        return is_file($this->binary($name));
    }

    /**
     * @return list<string>
     */
    public function analysisPaths(): array
    {
        return $this->framework->analysisPaths($this->root, $this->manifest);
    }

    /**
     * @return list<string>
     */
    public function testPaths(): array
    {
        $candidates = array_merge($this->manifest->autoloadDevDirectories(), ['tests']);

        return array_values(array_unique(array_filter(
            $candidates,
            fn (string $directory): bool => $directory !== '.' && is_dir($this->path($directory)),
        )));
    }

    /**
     * @return list<string>
     */
    public function refactorPaths(): array
    {
        $analysisPaths = $this->analysisPaths();

        if (in_array('.', $analysisPaths, true)) {
            return ['.'];
        }

        return array_values(array_unique(array_merge($analysisPaths, $this->testPaths())));
    }
}
