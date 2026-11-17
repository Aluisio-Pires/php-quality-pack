---
name: pqp-types
description: Fix PHPStan type errors through php-quality-pack. Use when the analyse step fails, when adding types or when PHPStan reports errors.
---

# Fix types with pqp

## Commands

- `vendor/bin/pqp analyse --json` or the `analyse` MCP tool. Each error has `file`, `line`, `message`, `identifier` and sometimes `tip`.
- Analyse one path: `pqp analyse src/Billing --json`.
- Try a stricter level without changing the config: `pqp analyse --level=8 --json`.

## How to fix

1. Read the `identifier`. Its documentation is at `https://phpstan.org/error-identifiers/<identifier>`.
2. Prefer native types: parameter, return and property types valid for the target PHP from `pqp detect`.
3. Add PHPDoc only where native types cannot express it:
   - `list<User>`, `array<string, int>`, `array{id: int, name: string}`
   - `@template T`, `@param class-string<T>`, `@return T`
   - `non-empty-string`, `positive-int`, `int<0, 100>`
4. Narrow `mixed` with checks (`is_string()`, `instanceof`, early returns) instead of casts.
5. Remove dead code that PHPStan proves unreachable.
6. Run `analyse` again, then `test`.

## Never without approval

- Lower `level` in `phpstan.neon.dist`.
- Add entries to `phpstan-baseline.neon` or regenerate it (`pqp analyse --baseline`).
- Add `@phpstan-ignore`, `@var` overrides or `assert()` only to silence an error.

## Raising the level

When the project is clean at its level, try `pqp analyse --level=<level + 1> --json`. If the errors are few, fix them and raise `level` in the config.
