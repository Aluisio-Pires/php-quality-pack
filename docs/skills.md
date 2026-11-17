# Skills

The skills live in [`resources/skills`](../resources/skills). Each one is a folder with a `SKILL.md` file: YAML frontmatter (`name`, `description`) followed by Markdown instructions.

| Skill | Triggers on |
| --- | --- |
| `pqp` | Any PHP change. Workflow, commands and rules. |
| `pqp-lint` | Style failures, formatting. |
| `pqp-refactor` | Rector failures, dead code, PHP or Laravel upgrades. |
| `pqp-types` | PHPStan errors, adding types. |
| `pqp-tests` | Writing or fixing Pest tests, type coverage. |

## Publishing

```bash
vendor/bin/pqp ai
vendor/bin/pqp ai --skills-path=.github/skills
```

The default path is `.claude/skills`, where Claude Code loads them as `/pqp`, `/pqp-types` and so on. Publishing again overwrites the `pqp*` folders with the installed version, so run it after updating the package.

Agents that do not support skills can read them through the MCP resources (`pqp://skills/<name>`) or from `vendor/aluisio/php-quality-pack/resources/skills`.

## AGENTS.md and CLAUDE.md

When these files exist, `pqp ai` adds or updates a short block between `<!-- pqp:start -->` and `<!-- pqp:end -->` with the commands and rules. Content outside the markers is never changed. Use `--no-guidelines` to skip it.

## Writing a new skill

1. Create `resources/skills/<name>/SKILL.md`.
2. Start with frontmatter: `name` matching the folder and a `description` that states when to use it.
3. Keep it short: commands first, then rules.
4. Run `composer test`. The MCP resource list picks it up automatically.
