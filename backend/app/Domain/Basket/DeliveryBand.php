<?php

declare(strict_types=1);

namespace App\Domain\Basket;

// One delivery price tier; a null limit means "this amount and above".
final readonly class DeliveryBand
{
    public function __construct(
        public Money $charge,
        public ?Money $below = null,
    ) {}

    public function covers(Money $amount): bool
    {
        return $this->below === null || $amount->cents < $this->below->cents;
    }
}
