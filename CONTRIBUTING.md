# Contributing

1. Fork and clone the repository.
2. `composer install`
3. Make the change with tests in `tests/Unit` or `tests/Feature`.
4. `composer test` must pass: Pint, Rector, PHPStan at level max and Pest.

## Code rules

- Source code must run on PHP 7.4: no union types, constructor promotion, `match`, enums, `readonly` or named arguments.
- No comments inside code. Use docblocks only for types PHP cannot express.
- Final classes, typed properties, small methods and descriptive names.
- Documentation goes in Markdown under `docs/`.
