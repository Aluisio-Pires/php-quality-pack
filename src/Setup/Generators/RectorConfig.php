<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup\Generators;

use Aluisio\PhpQualityPack\Project\Project;
use Aluisio\PhpQualityPack\Setup\RectorSet;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

final class RectorConfig
{
    public const FILE = 'rector.php';

    public const FULL = 'full';

    public const GRADUAL = 'gradual';

    private const OVERRIDE_ATTRIBUTE_RULE = AddOverrideAttributeToOverriddenMethodsRector::class;

    private const ROOT_EXCLUDES = ['vendor', 'node_modules', 'storage', 'bootstrap/cache', 'var'];

    private const PREPARED_SETS = ['DEAD_CODE', 'CODE_QUALITY', 'CODING_STYLE', 'TYPE_DECLARATION', 'PRIVATIZATION', 'EARLY_RETURN'];

    public function render(Project $project, string $mode): string
    {
        $paths = $project->refactorPaths();
        $uses = [\Rector\Config\RectorConfig::class];
        $chain = [$this->method('withPaths', $this->directories($paths))];
        $skips = $this->skips($paths);

        if ($mode === self::FULL && $project->targetPhp()->isAtLeast('8.3')) {
            $uses[] = self::OVERRIDE_ATTRIBUTE_RULE;
            $skips[] = 'AddOverrideAttributeToOverriddenMethodsRector::class';
        }

        if ($skips !== []) {
            $chain[] = $this->method('withSkip', $skips);
        }

        if ($mode === self::GRADUAL) {
            $chain[] = '->withDeadCodeLevel(0)';
            $chain[] = '->withCodeQualityLevel(0)';
            $chain[] = '->withTypeCoverageLevel(0)';

            return $this->file($uses, $chain);
        }

        $sets = array_map(
            static fn (string $constant): RectorSet => new RectorSet(SetList::class, $constant),
            self::PREPARED_SETS,
        );

        if ($project->manifest()->phpConstraint() !== null && $project->settings()->php() === null) {
            $chain[] = '->withPhpSets()';
        } else {
            $sets[] = new RectorSet(LevelSetList::class, 'UP_TO_PHP_'.$project->targetPhp()->compact());
        }

        $sets = array_values(array_filter(
            array_merge($sets, $project->framework()->rectorSets($project->installed())),
            static fn (RectorSet $set): bool => $set->isAvailable(),
        ));

        foreach ($sets as $set) {
            $uses[] = $set->className();
        }

        $chain[] = $this->method('withSets', array_map(static fn (RectorSet $set): string => $set->expression(), $sets));

        return $this->file($uses, $chain);
    }

    /**
     * @param  list<RectorSet>  $sets
     */
    public function renderUpgrade(Project $project, array $sets): string
    {
        $sets = array_values(array_filter($sets, static fn (RectorSet $set): bool => $set->isAvailable()));
        $uses = array_map(static fn (RectorSet $set): string => $set->className(), $sets);
        $paths = array_map(
            static fn (string $path): string => var_export($path === '.' ? $project->root() : $project->path($path), true),
            $project->refactorPaths(),
        );

        $chain = [$this->method('withPaths', $paths)];

        if (in_array('.', $project->refactorPaths(), true)) {
            $chain[] = $this->method('withSkip', array_map(
                static fn (string $path): string => var_export($project->path($path), true),
                self::ROOT_EXCLUDES,
            ));
        }

        $chain[] = $this->method('withSets', array_map(static fn (RectorSet $set): string => $set->expression(), $sets));

        return $this->file(array_merge([\Rector\Config\RectorConfig::class], $uses), $chain);
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function skips(array $paths): array
    {
        return in_array('.', $paths, true) ? $this->directories(self::ROOT_EXCLUDES) : [];
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function directories(array $paths): array
    {
        return array_map(static fn (string $path): string => $path === '.' ? '__DIR__' : "__DIR__.'/".$path."'", $paths);
    }

    /**
     * @param  list<string>  $items
     */
    private function method(string $name, array $items): string
    {
        $lines = array_map(static fn (string $item): string => '        '.$item.',', $items);

        return '->'.$name."([\n".implode("\n", $lines)."\n    ])";
    }

    /**
     * @param  list<string>  $uses
     * @param  list<string>  $chain
     */
    private function file(array $uses, array $chain): string
    {
        $uses = array_unique($uses);
        sort($uses);

        $imports = array_map(static fn (string $class): string => 'use '.$class.';', $uses);

        return "<?php\n\ndeclare(strict_types=1);\n\n"
            .implode("\n", $imports)
            ."\n\nreturn RectorConfig::configure()\n    "
            .implode("\n    ", $chain)
            .";\n";
    }
}
