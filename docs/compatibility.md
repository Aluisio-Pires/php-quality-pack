# Compatibility

## Runtime and target PHP

- **Runtime PHP** runs the tools. It must be 7.4 or higher.
- **Target PHP** is the version your code supports, read from `require.php`. PHPStan's `phpVersion` and Rector's PHP sets use it, so code that targets PHP 5.6 or 7.0 can be analysed with a modern runtime.

`config.platform.php` in `composer.json` sets the runtime used to choose packages.

## Tool selection

| Package | Constraint used | Minimum runtime |
| --- | --- | --- |
| `phpstan/phpstan` | `^1.12 \|\| ^2.0` | 7.4 (bundled) |
| `rector/rector` | `^1.2 \|\| ^2.0` | 7.4 (bundled) |
| `laravel/pint` | `^1.0` | 8.0 |
| `friendsofphp/php-cs-fixer` | `^3.0` | 7.4, used below 8.0 |
| `pestphp/pest` | `^1.23 \|\| ^2.0 \|\| ^3.0 \|\| ^4.0 \|\| ^5.0` | 7.3 |
| `pestphp/pest-plugin-type-coverage` | `^2.0 \|\| … \|\| ^5.0` | 8.1, optional |
| `larastan/larastan` | `^2.0 \|\| ^3.0` | 8.0, optional |
| `driftingly/rector-laravel` | `^1.0 \|\| ^2.0` | 7.2, optional |
| `pestphp/pest-plugin-laravel` | `^1.0 \|\| … \|\| ^5.0` | 7.3, optional |
| `phpstan/phpstan-symfony` | `^1.0 \|\| ^2.0` | 7.2, optional |

Composer resolves the highest version that fits the runtime and the project's other constraints.

## Version-aware behaviour

| Feature | Condition |
| --- | --- |
| Pint `--parallel` | Pint 1.24+ |
| PHPStan max level | 10 on PHPStan 2, 9 on PHPStan 1 |
| `phpunit.xml.dist` schema | `<source>` for Pest 2+, `<coverage>` for Pest 1 |
| Architecture presets | Pest 3+; a debug-function rule on Pest 2 |
| Type coverage | `pestphp/pest-plugin-type-coverage` installed |
| Laravel upgrade sets | `UP_TO_LARAVEL_<major>0` when rector-laravel provides it |
| Override attribute rule skipped | Target PHP 8.3+ (same as Nuno Maduro's setup) |

## Tested matrix

The test suite runs on PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5. See [`.github/workflows/tests.yml`](../.github/workflows/tests.yml).
