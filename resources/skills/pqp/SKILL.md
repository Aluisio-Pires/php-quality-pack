---
name: pqp
description: PHP code quality workflow with php-quality-pack (Pint, Rector, PHPStan, Pest). Use when writing or changing PHP code, fixing lint, type or test failures, modernizing code or before finishing a PHP task.
---

# PHP Quality Pack

`pqp` wraps Pint, Rector, PHPStan and Pest with configs calibrated to this project. Prefer the `pqp` MCP tools when available. Otherwise use the CLI with `--json` for machine-readable reports.

## Workflow

1. `vendor/bin/pqp detect --json`: learn the framework, target PHP, paths and enabled steps. Write code for the **target PHP**, not the runtime.
2. Make the change.
3. `vendor/bin/pqp fix`: apply Rector refactors and Pint formatting.
4. `vendor/bin/pqp check --json`: run every enabled gate.
5. Fix each failing step and repeat step 4 until `"passed": true`.

## Commands

| Goal | MCP tool | CLI |
| --- | --- | --- |
| Project profile | `detect` | `pqp detect --json` |
| All gates, read only | `check` | `pqp check --json` |
| Format | `lint` | `pqp lint [paths]` |
| Refactor preview | `refactor` | `pqp refactor --dry-run --json` |
| Refactor apply | `refactor` with `apply: true` | `pqp refactor` |
| Type errors | `analyse` | `pqp analyse --json` |
| Tests | `test` | `pqp test [paths] --filter=<name>` |
| Upgrade PHP/Laravel | `upgrade` | `pqp upgrade --php=8.4 --dry-run` |

Options the CLI does not know are forwarded to the tool: `pqp test --parallel`, `pqp analyse --memory-limit=2G`.

## Rules

- Never lower the PHPStan level, edit `phpstan-baseline.neon` or add `@phpstan-ignore` without the user's approval.
- Never remove a step from `pqp.json` to make `check` pass.
- Fix root causes. Do not silence errors with casts, `mixed` or wider types.
- Keep changes inside the task scope. Review `refactor` diffs before applying them to unrelated files.

## Focused skills

- `pqp-lint`: formatting and style rules.
- `pqp-refactor`: Rector refactors and PHP or framework upgrades.
- `pqp-types`: fixing PHPStan errors.
- `pqp-tests`: writing and fixing Pest tests.
