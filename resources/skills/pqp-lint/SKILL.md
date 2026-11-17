---
name: pqp-lint
description: Format PHP code with Pint (or PHP-CS-Fixer on PHP 7.4) through php-quality-pack. Use when the lint step fails or after editing PHP files.
---

# Lint with pqp

## Commands

- Fix: `vendor/bin/pqp lint [paths]` or the `lint` MCP tool.
- Report only: `vendor/bin/pqp lint --test` or `lint` with `test: true`.

Formatting is safe to apply. Run it after every edit instead of formatting by hand.

## Configuration

- Pint reads `pint.json`. PHP-CS-Fixer reads `.php-cs-fixer.dist.php`.
- The preset follows the framework: `laravel`, `symfony` or `per`.
- Risky rules (`declare_strict_types`, `final_class`, `strict_comparison`) are enabled only when the whole codebase already follows them. Do not enable them in an existing project without approval: they change runtime behaviour.

## When lint keeps failing

1. Run `pqp lint` (not `--test`) to apply the fixes.
2. If a file is generated or vendored, add it to `exclude` in `pint.json` instead of formatting it.
3. If two tools disagree, run `pqp fix`: it runs Rector first and Pint last, so Pint has the final word on style.
