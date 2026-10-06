<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

final readonly class BasketLine
{
    public function __construct(
        public Product $product,
        public int $quantity,
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Basket line quantity must be positive.');
        }
    }

    public function subtotal(): Money
    {
        return $this->product->unitPrice->times($this->quantity);
    }
}
