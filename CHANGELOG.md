# Changelog

## 1.0.0

- `pqp` CLI: `install`, `check`, `fix`, `lint`, `refactor`, `analyse`, `test`, `upgrade`, `detect`, `ai`, `mcp`.
- Tool selection by runtime PHP (7.4+) and framework (Laravel, Symfony, plain PHP).
- Configs calibrated to the project: PHPStan level by binary search, full or gradual Rector, risky Pint rules only when already followed, passing architecture presets, current type coverage.
- JSON reports for every command.
- MCP server with tools, prompts and skill resources.
- Agent skills: `pqp`, `pqp-lint`, `pqp-refactor`, `pqp-types`, `pqp-tests`.
