# Commands

All commands run from any directory inside the project. `pqp` finds the nearest `composer.json`.

Shared behaviour:

- `--json` prints a report instead of the tool's own output. Exit code `0` means every step passed.
- Unknown options and arguments after `--` go to the underlying tool.
- `--help` on any command lists its options.

## install

Install the tools that fit the project and generate calibrated configs.

```bash
vendor/bin/pqp install [options]
```

| Option | Effect |
| --- | --- |
| `--dry-run` | Print the packages that would be installed. |
| `--no-composer` | Skip package installation; only generate configs. |
| `--force` | Regenerate configs that already exist. |
| `--level=<n>` | Use this PHPStan level instead of calibrating. |
| `--baseline` | With `--level`: store current errors in `phpstan-baseline.neon`. |
| `--rector=<mode>` | `auto` (default), `full` or `gradual`. |
| `--no-calibrate` | Do not run the tools. Uses PHPStan level max and full Rector sets. |
| `--no-ai` | Skip skills, MCP registration and agent guidelines. |
| `--no-scripts` | Skip composer scripts. |

Required packages are installed together. Optional ones (framework extensions, Pest plugins) are installed one by one and skipped with a warning when they conflict with the project's dependencies.

## check

Run every enabled step without changing files: `lint --test`, `refactor --dry-run`, `analyse`, `test`, `test --type-coverage`.

```bash
vendor/bin/pqp check [--json]
```

Enabled steps come from `steps` in `pqp.json`. Without `pqp.json`, every installed tool runs.

## fix

Apply Rector, then Pint.

```bash
vendor/bin/pqp fix [paths...] [--json]
```

## lint

```bash
vendor/bin/pqp lint [paths...] [--test] [--json]
```

Uses Pint when installed, otherwise PHP-CS-Fixer. Pint runs with `--parallel` from version 1.24.

## refactor

```bash
vendor/bin/pqp refactor [paths...] [--dry-run] [--json]
```

The JSON report lists each changed file with the applied rules and the diff.

## analyse

```bash
vendor/bin/pqp analyse [paths...] [--level=<n>] [--json]
vendor/bin/pqp analyse --baseline
```

- `--level` overrides the configured level for one run. Use it to measure the next level.
- `--baseline` regenerates `phpstan-baseline.neon`.
- The memory limit defaults to 1G. Override it with `-- --memory-limit=2G`.

JSON errors contain `file`, `line`, `message`, `identifier` and `tip`.

## test

```bash
vendor/bin/pqp test [paths...] [pest options] [--json]
vendor/bin/pqp test --type-coverage
```

`--type-coverage` uses the `typeCoverage` minimum from `pqp.json` (requires `pestphp/pest-plugin-type-coverage`).

## upgrade

Upgrade code with a temporary Rector config that contains only the upgrade sets.

```bash
vendor/bin/pqp upgrade --php=8.4 --dry-run
vendor/bin/pqp upgrade --laravel=12
```

After a PHP upgrade, update `require.php` in `composer.json`.

## detect

```bash
vendor/bin/pqp detect [--json]
```

Prints the root, framework and version, target and runtime PHP, paths, installed tools, enabled steps and code conventions.

## ai

```bash
vendor/bin/pqp ai [--skills-path=<dir>] [--mcp=claude,cursor,vscode] [--no-skills] [--no-mcp] [--no-guidelines]
```

| Client | File |
| --- | --- |
| `claude` | `.mcp.json` |
| `cursor` | `.cursor/mcp.json` |
| `vscode` | `.vscode/mcp.json` |

## mcp

```bash
vendor/bin/pqp mcp
```

Starts the MCP server over stdio. See [mcp.md](mcp.md).
