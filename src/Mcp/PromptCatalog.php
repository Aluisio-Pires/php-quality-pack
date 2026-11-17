<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Mcp;

use InvalidArgumentException;

final class PromptCatalog
{
    private const PROMPTS = [
        'quality-gate' => [
            'title' => 'Make the quality gate pass',
            'description' => 'Run every quality gate and fix the failures until it passes.',
            'arguments' => [],
            'template' => "Use the pqp MCP tools to make this project pass its quality gate.\n\n1. Call `detect` to learn the project profile.\n2. Call `fix` to apply safe automatic fixes.\n3. Call `check` and fix every remaining failure in the code. Prefer real fixes over ignores.\n4. Repeat `check` until `passed` is true.\n\nNever lower the PHPStan level, edit phpstan-baseline.neon or add @phpstan-ignore without asking me first.",
        ],
        'fix-types' => [
            'title' => 'Fix PHPStan errors',
            'description' => 'Fix PHPStan errors in a path without baselines or ignores.',
            'arguments' => [
                ['name' => 'path', 'description' => 'File or directory to fix. Empty means the whole project.', 'required' => false],
            ],
            'template' => "Call the pqp `analyse` tool for {path} and fix every error.\n\nUse precise native types first, then PHPDoc generics and array shapes (`list<User>`, `array{id: int}`). Narrow mixed values with checks instead of casts. Do not add @phpstan-ignore, do not change the level and do not touch the baseline. Call `analyse` again until no errors remain, then call `test`.",
        ],
        'write-tests' => [
            'title' => 'Write Pest tests',
            'description' => 'Write Pest tests for a class or file.',
            'arguments' => [
                ['name' => 'target', 'description' => 'Class, file or feature to test.', 'required' => true],
            ],
            'template' => "Write Pest tests for {target}.\n\nCall `detect` to find the test paths and Pest version. Follow the existing test style. Use `it()` / `test()` with `expect()`, datasets for input variations and one behaviour per test. Cover success, edge and failure cases. Call `test` with the new file until it passes, then call `analyse` and `lint`.",
        ],
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach (self::PROMPTS as $name => $prompt) {
            $definitions[] = [
                'name' => $name,
                'title' => $prompt['title'],
                'description' => $prompt['description'],
                'arguments' => $prompt['arguments'],
            ];
        }

        return $definitions;
    }

    /**
     * @param  array<mixed>  $arguments
     * @return array<string, mixed>
     */
    public function get(string $name, array $arguments): array
    {
        if (! isset(self::PROMPTS[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown prompt [%s].', $name));
        }

        $prompt = self::PROMPTS[$name];
        $text = $prompt['template'];

        foreach ($prompt['arguments'] as $argument) {
            $value = $arguments[$argument['name']] ?? '';
            $text = str_replace('{'.$argument['name'].'}', is_string($value) && $value !== '' ? $value : 'the whole project', $text);
        }

        return [
            'description' => $prompt['description'],
            'messages' => [
                ['role' => 'user', 'content' => ['type' => 'text', 'text' => $text]],
            ],
        ];
    }
}
