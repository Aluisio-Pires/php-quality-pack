---
name: pqp-refactor
description: Refactor and modernize PHP code with Rector through php-quality-pack, including PHP and Laravel version upgrades. Use when the refactor step fails, when cleaning dead code or when upgrading PHP or Laravel.
---

# Refactor with pqp

## Commands

- Preview: `vendor/bin/pqp refactor --dry-run --json` or the `refactor` MCP tool. Each change lists the file, the Rector rules and the diff.
- Apply: `vendor/bin/pqp refactor [paths]` or `refactor` with `apply: true`.
- Upgrade: `vendor/bin/pqp upgrade --php=8.4 --dry-run`, `vendor/bin/pqp upgrade --laravel=12 --dry-run`. Drop `--dry-run` (or pass `apply: true`) to write the changes.

## Configuration modes (`rector.php`)

- **full**: prepared sets (dead code, code quality, coding style, type declarations, privatization, early return), PHP sets from `composer.json` and framework sets. Used when the code already passes.
- **gradual**: `withDeadCodeLevel`, `withCodeQualityLevel` and `withTypeCoverageLevel` start at `0`. Used for codebases with many pending refactors. Raise one level at a time, run `pqp refactor`, then `pqp check`.

## Rules

- Preview before applying on code outside the task scope.
- After applying, run `pqp lint` and `pqp test`.
- After `upgrade --php`, update `require.php` in `composer.json` so future runs target the new version.
- To keep a file untouched, add it to `withSkip()` in `rector.php` and explain why.
