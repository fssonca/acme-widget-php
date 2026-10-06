<?php

declare(strict_types=1);

namespace App\Domain\Basket;

interface Offer
{
    /** @param list<BasketLine> $lines */
    public function discountFor(array $lines): Money;
}
