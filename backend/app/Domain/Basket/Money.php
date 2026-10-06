<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(public int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public function plus(self $other): self
    {
        if ($other->cents > PHP_INT_MAX - $this->cents) {
            throw new InvalidArgumentException('Money addition exceeds the integer range.');
        }

        return new self($this->cents + $other->cents);
    }

    public function minus(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function times(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Money multiplier cannot be negative.');
        }

        if ($quantity > 0 && $this->cents > intdiv(PHP_INT_MAX, $quantity)) {
            throw new InvalidArgumentException('Money multiplication exceeds the integer range.');
        }

        return new self($this->cents * $quantity);
    }

    public function format(): string
    {
        return intdiv($this->cents, 100).'.'.str_pad((string) ($this->cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
