<?php

declare(strict_types=1);

namespace App\Domain\Basket;

final readonly class BasketTotals
{
    public Money $discountedSubtotal;

    public Money $total;

    /** @param list<BasketLine> $lines */
    public function __construct(
        public array $lines,
        public Money $subtotal,
        public Money $discount,
        public Money $delivery,
    ) {
        $this->discountedSubtotal = $subtotal->minus($discount);
        $this->total = $this->discountedSubtotal->plus($delivery);
    }
}
