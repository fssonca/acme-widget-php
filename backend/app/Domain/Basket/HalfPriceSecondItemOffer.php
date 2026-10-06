<?php

declare(strict_types=1);

namespace App\Domain\Basket;

// "Buy one, get the second half price", repeated for every complete pair of the target product.
final readonly class HalfPriceSecondItemOffer implements Offer
{
    public function __construct(private string $productCode) {}

    public function discountFor(array $lines): Money
    {
        $discount = new Money(0);

        foreach ($lines as $line) {
            if ($line->product->code !== $this->productCode) {
                continue;
            }

            // Each half-price unit is rounded down to whole cents: $32.95 -> $16.47, saving $16.48 per pair.
            $price = $line->product->unitPrice->cents;
            $savingPerPair = new Money($price - intdiv($price, 2));
            $discount = $discount->plus($savingPerPair->times(intdiv($line->quantity, 2)));
        }

        return $discount;
    }
}
