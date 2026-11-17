# PHP Quality Pack

One command to lint, refactor, type-check and test any PHP project, with configs calibrated to the project's current state and first-class support for AI agents.

`pqp` wraps [Pint](https://github.com/laravel/pint), [Rector](https://github.com/rectorphp/rector), [PHPStan](https://github.com/phpstan/phpstan) and [Pest](https://github.com/pestphp/pest). It ships an MCP server and a bank of agent skills. The defaults follow [Nuno Maduro](https://github.com/nunomaduro)'s setups ([skeleton-php](https://github.com/nunomaduro/skeleton-php), [essentials](https://github.com/nunomaduro/essentials)), adapted so they pass on the code you have today.

- **Lint**: Pint (PHP-CS-Fixer on PHP 7.4) with the framework preset.
- **Refactor**: Rector prepared sets, PHP version sets and framework sets.
- **Types**: PHPStan at the highest level your code already passes.
- **Tests**: Pest with architecture presets and type coverage.
- **AI**: an MCP server (`pqp`), agent skills (`/pqp`) and JSON reports.

## Requirements

- PHP 7.4 or higher to run the tools. The analysed code can target older versions.
- Composer 2.

## Installation

```bash
composer require --dev aluisio/php-quality-pack
vendor/bin/pqp install
```

`pqp install`:

1. Detects the framework, the target PHP (from `composer.json`) and the runtime PHP.
2. Installs the tool versions that run on your PHP: Pint or PHP-CS-Fixer, Pest 1 to 5, Larastan, rector-laravel, phpstan-symfony and Pest plugins.
3. Generates configs from the project's state:

| File | How it adapts |
| --- | --- |
| `pint.json` / `.php-cs-fixer.dist.php` | Framework preset. Risky rules (`declare_strict_types`, `final_class`, `strict_comparison`) only when the whole codebase already follows them. |
| `rector.php` | Full rule set when the code already passes it, otherwise gradual levels starting at `0`. |
| `phpstan.neon.dist` | Highest clean level found by binary search. A baseline only when level 0 fails. |
| `phpunit.xml.dist`, `tests/Pest.php` | Created only when missing, for the installed Pest/PHPUnit version. |
| `tests/ArchTest.php` | Only the Pest architecture presets the code satisfies. |
| `pqp.json` | Steps that pass today and the current type coverage as the minimum. |

4. Adds `composer quality` and `composer quality:fix` scripts.
5. Publishes the agent skills to `.claude/skills`, registers the MCP server in `.mcp.json` and updates `AGENTS.md` / `CLAUDE.md` when they exist.

Existing configs are kept. Use `--force` to regenerate them. See [docs/configuration.md](docs/configuration.md).

## Usage

| Command | What it does |
| --- | --- |
| `pqp check` | Run every enabled gate without changing files. Use it in CI. |
| `pqp fix [paths]` | Apply Rector, then Pint. |
| `pqp lint [paths] [--test]` | Format code, or only report with `--test`. |
| `pqp refactor [paths] [--dry-run]` | Refactor with Rector. |
| `pqp analyse [paths] [--level=<n>] [--baseline]` | Type-check with PHPStan. |
| `pqp test [paths] [--type-coverage]` | Run Pest, or check type coverage. |
| `pqp upgrade --php=<version> [--laravel=<major>] [--dry-run]` | Upgrade the code to a newer PHP or Laravel version. |
| `pqp detect` | Show the detected profile. |
| `pqp ai` | Publish skills and register the MCP server. |
| `pqp mcp` | Start the MCP server over stdio. |

- Add `--json` to `check`, `fix`, `lint`, `refactor`, `analyse`, `test`, `upgrade` and `detect` for machine-readable reports.
- Unknown options and anything after `--` are forwarded to the tool: `pqp test --parallel`, `pqp analyse -- --memory-limit=2G`.
- Run `pqp help <command>` for every option. Full reference: [docs/commands.md](docs/commands.md).

Typical loop:

```bash
vendor/bin/pqp fix
vendor/bin/pqp check
```

CI:

```yaml
- run: composer install --no-interaction
- run: vendor/bin/pqp check
```

## AI agents

### MCP server

`pqp install` and `pqp ai` register the server in `.mcp.json`:

```json
{
    "mcpServers": {
        "pqp": {
            "command": "php",
            "args": ["vendor/bin/pqp", "mcp"]
        }
    }
}
```

Other clients: `pqp ai --mcp=claude,cursor,vscode`. Or with the Claude Code CLI: `claude mcp add pqp -- php vendor/bin/pqp mcp`.

| Tool | Purpose | Changes files |
| --- | --- | --- |
| `detect` | Project profile. Call first. | No |
| `check` | Every enabled gate. | No |
| `lint` | Format (`test: true` to only report). | Yes |
| `refactor` | Rector diff per file (`apply: true` to write). | Only with `apply` |
| `analyse` | PHPStan errors with file, line, identifier and tip. | No |
| `test` | Pest (`filter`, `paths`, `type_coverage`). | No |
| `fix` | Rector, then Pint. | Yes |
| `upgrade` | PHP/Laravel upgrade (`apply: true` to write). | Only with `apply` |

Prompts: `quality-gate`, `fix-types`, `write-tests`. Resources: each skill at `pqp://skills/<name>`. Details: [docs/mcp.md](docs/mcp.md).

### Skills

| Skill | Use |
| --- | --- |
| `/pqp` | Overall workflow and rules. |
| `/pqp-lint` | Formatting. |
| `/pqp-refactor` | Rector and upgrades. |
| `/pqp-types` | Fixing PHPStan errors. |
| `/pqp-tests` | Writing and fixing Pest tests. |

Skills follow the Agent Skills format (`SKILL.md` with frontmatter). Publish them elsewhere with `pqp ai --skills-path=<dir>`. Details: [docs/skills.md](docs/skills.md).

### Instructions for AI agents

1. Run `vendor/bin/pqp detect --json` (or the `detect` tool). Write code for `targetPhp`.
2. After editing, run `vendor/bin/pqp fix`, then `vendor/bin/pqp check --json`.
3. Fix every failing step until `"passed": true`.
4. Never lower the PHPStan level, edit `phpstan-baseline.neon`, add `@phpstan-ignore` or remove steps from `pqp.json` without the user's approval.

## Compatibility

| Runtime PHP | Formatter | Pest | PHPStan | Rector | Type coverage |
| --- | --- | --- | --- | --- | --- |
| 7.4 | PHP-CS-Fixer 3 | 1 | 2 | 2 | No |
| 8.0 | Pint 1 | 1 | 2 | 2 | No |
| 8.1 | Pint 1 | 2 | 2 | 2 | Yes |
| 8.2 | Pint 1 | 3 | 2 | 2 | Yes |
| 8.3 | Pint 1 | 4 | 2 | 2 | Yes |
| 8.4+ | Pint 1 | 5 | 2 | 2 | Yes |

Composer picks the highest version that fits your PHP and your existing dependencies. A project locked to PHPUnit 9 gets Pest 1 even on PHP 8.3.

| Framework | Extras |
| --- | --- |
| Laravel (apps and packages) | Larastan, rector-laravel with your Laravel version, pest-plugin-laravel, `laravel` Pint and arch presets. |
| Symfony | phpstan-symfony, Rector Symfony sets, `symfony` Pint preset. |
| Plain PHP and legacy code | Paths from Composer autoload, or the project root without autoload. PER preset. |

More: [docs/compatibility.md](docs/compatibility.md).

## Development

```bash
composer install
composer test
```

`composer test` runs `pqp check` on this package: Pint, Rector, PHPStan at level max and Pest.

## License

MIT. See [LICENSE.md](LICENSE.md).
