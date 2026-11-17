<?php

declare(strict_types=1);

namespace Acme\Money;

final class Money
{
    private int $amount;

    public function __construct(int $amount)
    {
        $this->amount = $amount;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function name(): string
    {
        return self::class;
    }
}
