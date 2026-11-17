<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Ai;

use Aluisio\PhpQualityPack\Support\Filesystem;

final class AgentGuidelines
{
    public const FILES = ['AGENTS.md', 'CLAUDE.md'];

    private const START = '<!-- pqp:start -->';

    private const END = '<!-- pqp:end -->';

    private Filesystem $filesystem;

    public function __construct(?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem;
    }

    /**
     * @return list<string>
     */
    public function update(string $root): array
    {
        $updated = [];

        foreach (self::FILES as $file) {
            $path = $root.'/'.$file;

            if (! $this->filesystem->exists($path)) {
                continue;
            }

            $this->filesystem->write($path, $this->upsert($this->filesystem->read($path)));
            $updated[] = $file;
        }

        return $updated;
    }

    public function upsert(string $contents): string
    {
        $block = self::START."\n".$this->block()."\n".self::END;
        $pattern = '/'.preg_quote(self::START, '/').'.*?'.preg_quote(self::END, '/').'/s';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $block, $contents);
        }

        return rtrim($contents)."\n\n".$block."\n";
    }

    private function block(): string
    {
        return <<<'MARKDOWN'
## PHP code quality

This project uses [php-quality-pack](https://github.com/Aluisio-Pires/php-quality-pack). Use the `pqp` MCP server when available, otherwise the CLI:

- `vendor/bin/pqp check --json`: run every quality gate and get a machine-readable report.
- `vendor/bin/pqp fix`: apply Rector refactors, then format with Pint.
- `vendor/bin/pqp analyse --json`: PHPStan errors with file, line and identifier.
- `vendor/bin/pqp test`: run Pest.

Rules: run `pqp check` before finishing a task. Never lower the PHPStan level, edit `phpstan-baseline.neon` or add `@phpstan-ignore` without approval.
MARKDOWN;
    }
}
