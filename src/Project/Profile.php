<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project;

use Aluisio\PhpQualityPack\Tools\Toolchain;

final class Profile
{
    private const PACKAGES = [
        'pint' => 'laravel/pint',
        'php-cs-fixer' => 'friendsofphp/php-cs-fixer',
        'phpstan' => 'phpstan/phpstan',
        'rector' => 'rector/rector',
        'pest' => 'pestphp/pest',
    ];

    private ConventionScanner $scanner;

    public function __construct(?ConventionScanner $scanner = null)
    {
        $this->scanner = $scanner ?? new ConventionScanner;
    }

    /**
     * @return array{
     *     root: string,
     *     name: string,
     *     framework: string,
     *     frameworkVersion: string|null,
     *     targetPhp: string,
     *     runtimePhp: string,
     *     analysisPaths: list<string>,
     *     testPaths: list<string>,
     *     steps: list<string>,
     *     tools: array<string, string|null>,
     *     conventions: array<string, int|bool>,
     *     settingsFile: string|null
     * }
     */
    public function build(Project $project): array
    {
        $tools = [];

        foreach (self::PACKAGES as $tool => $package) {
            $tools[$tool] = $project->hasBinary($tool) ? ($project->installed()->version($package) ?? 'installed') : null;
        }

        return [
            'root' => $project->root(),
            'name' => $project->manifest()->name(),
            'framework' => $project->framework()->name(),
            'frameworkVersion' => $project->framework()->version($project->installed()),
            'targetPhp' => $project->targetPhp()->toString(),
            'runtimePhp' => $project->runtimePhp()->toString(),
            'analysisPaths' => $project->analysisPaths(),
            'testPaths' => $project->testPaths(),
            'steps' => (new Toolchain($project))->enabledSteps(),
            'tools' => $tools,
            'conventions' => $this->scanner->scan($project->root(), $project->analysisPaths())->toArray(),
            'settingsFile' => is_file($project->path(Settings::FILE)) ? Settings::FILE : null,
        ];
    }
}
