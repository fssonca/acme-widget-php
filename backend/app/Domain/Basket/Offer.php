<?php

declare(strict_types=1);

namespace App\Domain\Basket;

// Strategy: each offer inspects the basket lines and returns the amount it takes off.
interface Offer
{
    /** @param list<BasketLine> $lines */
    public function discountFor(array $lines): Money;

    // Shown to customers, so the UI never describes an offer that is not applied.
    public function description(): string;
}
