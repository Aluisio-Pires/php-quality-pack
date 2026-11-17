# Configuration

`pqp` keeps each tool's own config file, so every tool also works on its own. `pqp.json` only stores what the tools cannot express.

## pqp.json

```json
{
    "steps": ["lint", "refactor", "analyse", "test", "type-coverage"],
    "typeCoverage": 97.5
}
```

| Key | Type | Default | Meaning |
| --- | --- | --- | --- |
| `steps` | list | every installed tool | Steps run by `pqp check`: `lint`, `refactor`, `analyse`, `test`, `type-coverage`. |
| `typeCoverage` | number | none | Minimum type coverage. Without it, `type-coverage` is skipped. |
| `php` | string | lowest version in `require.php` | Target PHP for the analysed code. |
| `framework` | string | detected | `laravel`, `symfony` or `php`. |

`pqp install` writes `steps` and `typeCoverage`. A step that fails during installation is left out and reported with a hint.

### Enabling a pending step

1. Make it pass: `pqp lint`, `pqp refactor` or fix the tests.
2. Add it to `steps`.
3. Run `pqp check`.

## Detection

| Value | Source |
| --- | --- |
| Project root | Nearest directory with `composer.json`. |
| Target PHP | `php` in `pqp.json`, else the lowest version in `require.php`, else the runtime. |
| Runtime PHP | `config.platform.php`, else the PHP running `pqp`. |
| Framework | `laravel/framework`, `illuminate/support` or `orchestra/testbench` → Laravel; `symfony/framework-bundle` → Symfony; otherwise plain PHP. |
| Analysis paths | Laravel: `app`, `config`, `database`, `routes`. Others: Composer autoload directories, else `src`, else the project root. |
| Test paths | `autoload-dev` directories and `tests`. |

## Generated files

### pint.json / .php-cs-fixer.dist.php

- Preset: `laravel`, `symfony` or `per` (`@PSR12` / `@Symfony` for PHP-CS-Fixer).
- Always on: imports, ordered class elements, no useless `else`, lowercase keywords and similar safe rules.
- On only when the code already complies: `declare_strict_types`, `final_class`, `strict_comparison`.
- Targets below PHP 8.0 keep trailing commas valid for that version.

### rector.php

- **full**: `withPhpSets()` (or `LevelSetList::UP_TO_PHP_XX` when `require.php` is missing), dead code, code quality, coding style, type declarations, privatization, early return, plus Laravel or Symfony sets.
- **gradual**: `withDeadCodeLevel(0)`, `withCodeQualityLevel(0)`, `withTypeCoverageLevel(0)`. Raise one level at a time.

### phpstan.neon.dist

- `level`: highest level with zero errors, or `max`.
- `phpVersion`: the target PHP.
- Larastan or phpstan-symfony included when installed.
- `phpstan-baseline.neon` included only when level 0 had errors.

Raise the level by checking the next one first: `pqp analyse --level=7 --json`.

### Pest

- `phpunit.xml.dist` when no PHPUnit config exists, with the schema for the installed PHPUnit.
- `tests/Pest.php` for Laravel apps, binding `Feature` tests to `Tests\TestCase`.
- `tests/ArchTest.php` with the passing presets among `php`, `security` and `laravel` (Pest 3+), or a debug-function rule (Pest 2).

## Regenerating

```bash
vendor/bin/pqp install --no-composer --force
```

This recalibrates everything against the current code.
