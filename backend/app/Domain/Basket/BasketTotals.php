<?php

declare(strict_types=1);

namespace App\Domain\Basket;

// Immutable result of one basket calculation; later additions to the basket do not change it.
final readonly class BasketTotals
{
    public Money $total;

    /** @param list<BasketLine> $lines */
    public function __construct(
        public array $lines,
        public Money $subtotal,
        public Money $discount,
        public Money $delivery,
    ) {
        $this->total = $subtotal->minus($discount)->plus($delivery);
    }
}
