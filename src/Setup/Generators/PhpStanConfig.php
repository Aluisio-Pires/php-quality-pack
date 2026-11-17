<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup\Generators;

use Aluisio\PhpQualityPack\Project\Project;
use Aluisio\PhpQualityPack\Tools\PhpStan;

final class PhpStanConfig
{
    public const FILE = 'phpstan.neon.dist';

    public const FILES = ['phpstan.neon', 'phpstan.neon.dist', 'phpstan.dist.neon'];

    private const ROOT_EXCLUDES = ['vendor', 'node_modules', 'storage', 'bootstrap/cache', 'var'];

    /**
     * @param  int|string  $level
     */
    public function render(Project $project, $level, bool $baseline): string
    {
        $paths = $project->analysisPaths();
        $includes = $project->framework()->phpStanIncludes($project->installed());

        if ($baseline) {
            $includes[] = PhpStan::BASELINE;
        }

        $lines = [];

        if ($includes !== []) {
            $lines[] = 'includes:';
            $lines = array_merge($lines, $this->list($includes, 1), ['']);
        }

        $lines[] = 'parameters:';
        $lines[] = '    level: '.$level;
        $lines[] = '    phpVersion: '.$project->targetPhp()->id();
        $lines[] = '    paths:';
        $lines = array_merge($lines, $this->list($paths, 2));

        if (in_array('.', $paths, true)) {
            $lines[] = '    excludePaths:';
            $lines = array_merge($lines, $this->list(self::ROOT_EXCLUDES, 2));
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<string>  $items
     * @return list<string>
     */
    private function list(array $items, int $depth): array
    {
        return array_map(static fn (string $item): string => str_repeat('    ', $depth).'- '.$item, $items);
    }
}
