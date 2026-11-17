<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Mcp;

use Aluisio\PhpQualityPack\Console\Input;
use InvalidArgumentException;

final class ToolCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function definitions(): array
    {
        $paths = ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Files or directories relative to the project root. Empty means the configured paths.'];

        return [
            $this->tool('detect', 'Detect project profile', 'Return the project profile: framework, target and runtime PHP, analysis paths, installed tools, enabled check steps and code conventions. Call this first.', [], true),
            $this->tool('check', 'Run all quality gates', 'Run every enabled quality gate without changing files: lint, Rector dry run, PHPStan, Pest and type coverage. Returns one report per step. Run before finishing a task.', [], true),
            $this->tool('lint', 'Format code', 'Format PHP files with Pint (PHP-CS-Fixer on PHP 7.4). Formatting is safe to apply. Set test=true to only report.', [
                'paths' => $paths,
                'test' => ['type' => 'boolean', 'description' => 'Report issues without changing files.', 'default' => false],
            ], false),
            $this->tool('refactor', 'Refactor with Rector', 'Refactor and modernize code with Rector. Dry run by default: returns the diff and rule per file. Set apply=true to write the changes.', [
                'paths' => $paths,
                'apply' => ['type' => 'boolean', 'description' => 'Write the changes to disk.', 'default' => false],
            ], false),
            $this->tool('analyse', 'Analyse types with PHPStan', 'Run PHPStan and return each error with file, line, message, identifier and tip.', [
                'paths' => $paths,
                'level' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10, 'description' => 'Override the configured level for this run.'],
            ], true),
            $this->tool('test', 'Run Pest tests', 'Run the Pest test suite and return the result and output.', [
                'paths' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Test files or directories.'],
                'filter' => ['type' => 'string', 'description' => 'Run only tests whose name matches this pattern.'],
                'type_coverage' => ['type' => 'boolean', 'description' => 'Check type coverage instead of running tests.', 'default' => false],
            ], true),
            $this->tool('fix', 'Fix code', 'Apply Rector refactors, then Pint formatting.', [
                'paths' => $paths,
            ], false),
            $this->tool('upgrade', 'Upgrade PHP or Laravel version', 'Upgrade code to a newer PHP or Laravel version with Rector. Dry run by default. Set apply=true to write the changes.', [
                'php' => ['type' => 'string', 'description' => 'Target PHP version, e.g. 8.3.'],
                'laravel' => ['type' => 'integer', 'description' => 'Target Laravel major version, e.g. 12.'],
                'apply' => ['type' => 'boolean', 'description' => 'Write the changes to disk.', 'default' => false],
            ], false),
        ];
    }

    /**
     * @param  array<mixed>  $arguments
     */
    public function input(string $tool, array $arguments): Input
    {
        $paths = $this->strings($arguments['paths'] ?? []);

        switch ($tool) {
            case 'detect':
            case 'check':
                return new Input($tool, [], ['json' => true]);
            case 'lint':
                return new Input($tool, $paths, $this->flags(['json' => true, 'test' => ($arguments['test'] ?? false) === true]));
            case 'refactor':
                return new Input($tool, $paths, $this->flags(['json' => true, 'dry-run' => ($arguments['apply'] ?? false) !== true]));
            case 'analyse':
                return new Input($tool, $paths, $this->flags(['json' => true], ['level' => $arguments['level'] ?? null]));
            case 'test':
                return new Input($tool, $this->strings($arguments['paths'] ?? []), $this->flags(
                    ['json' => true, 'type-coverage' => ($arguments['type_coverage'] ?? false) === true],
                    ['filter' => $arguments['filter'] ?? null],
                ));
            case 'fix':
                return new Input($tool, $paths, ['json' => true]);
            case 'upgrade':
                return new Input($tool, [], $this->flags(
                    ['json' => true, 'dry-run' => ($arguments['apply'] ?? false) !== true],
                    ['php' => $arguments['php'] ?? null, 'laravel' => $arguments['laravel'] ?? null],
                ));
            default:
                throw new InvalidArgumentException(sprintf('Unknown tool [%s].', $tool));
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function tool(string $name, string $title, string $description, array $properties, bool $readOnly): array
    {
        return [
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'inputSchema' => [
                'type' => 'object',
                'properties' => $properties === [] ? (object) [] : $properties,
            ],
            'annotations' => [
                'title' => $title,
                'readOnlyHint' => $readOnly,
                'destructiveHint' => false,
                'idempotentHint' => true,
                'openWorldHint' => false,
            ],
        ];
    }

    /**
     * @param  array<string, bool>  $flags
     * @param  array<string, mixed>  $values
     * @return array<string, string|true>
     */
    private function flags(array $flags, array $values = []): array
    {
        $options = array_map(static fn (): bool => true, array_filter($flags));

        foreach ($values as $name => $value) {
            if (is_scalar($value) && $value !== '') {
                $options[$name] = (string) $value;
            }
        }

        return $options;
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    private function strings($value): array
    {
        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }
}
