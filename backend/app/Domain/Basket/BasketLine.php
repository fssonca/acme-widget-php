<?php

declare(strict_types=1);

namespace App\Domain\Basket;

final readonly class BasketLine
{
    public function __construct(
        public Product $product,
        public int $quantity,
    ) {}

    public function subtotal(): Money
    {
        return $this->product->unitPrice->times($this->quantity);
    }
}
