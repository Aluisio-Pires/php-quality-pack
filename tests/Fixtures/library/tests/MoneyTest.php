<?php

declare(strict_types=1);

use Acme\Money\Money;

it('compares amounts', function () {
    expect((new Money(10))->equals(new Money(10)))->toBeTrue();
});
