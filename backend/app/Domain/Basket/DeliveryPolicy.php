<?php

declare(strict_types=1);

namespace App\Domain\Basket;

// Strategy: prices delivery from the merchandise total after offers.
interface DeliveryPolicy
{
    public function chargeFor(Money $discountedSubtotal): Money;
}
