---
name: pqp-tests
description: Write, run and fix Pest tests through php-quality-pack, including architecture tests and type coverage. Use when adding features, fixing bugs or when the test or type-coverage step fails.
---

# Test with pqp

## Commands

- All tests: `vendor/bin/pqp test` or the `test` MCP tool.
- One file or filter: `pqp test tests/Unit/MoneyTest.php`, `pqp test --filter="adds money"`.
- Type coverage: `pqp test --type-coverage` (minimum stored in `pqp.json`).
- Other Pest options are forwarded: `pqp test --parallel`, `pqp test --coverage`.

## Writing tests

Check the Pest major version with `pqp detect --json` and follow the existing test style.

```php
it('adds money in the same currency', function () {
    $total = Money::of(10, 'EUR')->add(Money::of(5, 'EUR'));

    expect($total->amount())->toBe(15);
});

it('rejects invalid amounts', function (int $amount) {
    Money::of($amount, 'EUR');
})->with([-1, -100])->throws(InvalidArgumentException::class);
```

- One behaviour per test, named as a sentence.
- Use datasets (`->with()`) for input variations.
- Cover the success path, edge cases and failures.
- Laravel: feature tests go in `tests/Feature` and use `Tests\TestCase` through `tests/Pest.php`.

## Architecture tests

`tests/ArchTest.php` holds the presets the code already satisfies (Pest 3+):

```php
arch()->preset()->php();
arch()->preset()->security();
```

Add custom rules when a convention matters, for example `arch()->expect('App\Models')->toExtend('Illuminate\Database\Eloquent\Model');`.

## Fixing failures

1. Run the failing file alone with `--filter`.
2. Fix the code when the test describes the intended behaviour. Fix the test only when the requirement changed.
3. Never delete or skip a failing test to make `check` pass.
4. Type coverage below the minimum: add native parameter, return and property types to the reported files.
