<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

// Integer cents avoid floating-point drift in price arithmetic.
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
        return new self($this->cents + $other->cents);
    }

    public function minus(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function times(int $quantity): self
    {
        return new self($this->cents * $quantity);
    }

    public function format(): string
    {
        return sprintf('%d.%02d', intdiv($this->cents, 100), $this->cents % 100);
    }
}
