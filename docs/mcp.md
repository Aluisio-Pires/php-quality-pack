# MCP server

`vendor/bin/pqp mcp` starts a [Model Context Protocol](https://modelcontextprotocol.io) server over stdio. It has no dependencies and runs on PHP 7.4+.

- Server name: `pqp`
- Protocol versions: `2025-11-25`, `2025-06-18`, `2025-03-26`, `2024-11-05`
- Capabilities: tools, prompts, resources

## Registration

```bash
vendor/bin/pqp ai --mcp=claude,cursor,vscode
```

Manual entry for clients that use `mcpServers`:

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

The client must start the server from the project root, or from any directory inside it.

## Tools

Every tool returns one text content item with a JSON report. `isError` is `true` only when the tool could not run, for example a missing binary or invalid arguments. A failing quality gate returns `"passed": false`.

### detect

No arguments. Returns the project profile.

### check

No arguments. Runs every enabled step without changing files.

```json
{
    "passed": false,
    "steps": [
        {"step": "lint", "command": "php vendor/bin/pint --test --parallel", "passed": true, "exitCode": 0, "output": "…"},
        {"step": "analyse", "command": "php vendor/bin/phpstan analyse …", "passed": false, "exitCode": 1, "errorCount": 1, "errors": [
            {"file": "app/Models/User.php", "line": 12, "message": "…", "identifier": "return.type"}
        ]}
    ]
}
```

### lint

| Argument | Type | Default |
| --- | --- | --- |
| `paths` | string[] | configured paths |
| `test` | boolean | `false` |

### refactor

| Argument | Type | Default |
| --- | --- | --- |
| `paths` | string[] | configured paths |
| `apply` | boolean | `false` |

Each change contains `file`, `rules` and `diff`.

### analyse

| Argument | Type | Default |
| --- | --- | --- |
| `paths` | string[] | configured paths |
| `level` | integer | configured level |

### test

| Argument | Type | Default |
| --- | --- | --- |
| `paths` | string[] | all tests |
| `filter` | string | none |
| `type_coverage` | boolean | `false` |

### fix

| Argument | Type | Default |
| --- | --- | --- |
| `paths` | string[] | configured paths |

### upgrade

| Argument | Type | Default |
| --- | --- | --- |
| `php` | string | none |
| `laravel` | integer | none |
| `apply` | boolean | `false` |

## Prompts

| Name | Arguments | Purpose |
| --- | --- | --- |
| `quality-gate` | none | Fix everything until `check` passes. |
| `fix-types` | `path` (optional) | Fix PHPStan errors without ignores. |
| `write-tests` | `target` | Write Pest tests for a class or feature. |

## Resources

Each skill is available as `pqp://skills/<name>` (`text/markdown`), for clients without native skill support.

## Output stability

Some tools change their output when they detect an AI agent through environment variables. In `--json` and MCP mode `pqp` runs them without those variables and with `PAO_DISABLE=1`, so the reports always have the format described here.
