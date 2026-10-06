<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

final readonly class HalfPriceSecondItemOffer implements Offer
{
    public function __construct(private string $targetCode)
    {
        if (trim($targetCode) === '') {
            throw new InvalidArgumentException('Offer target code must not be blank.');
        }
    }

    public function discountFor(array $lines): Money
    {
        $discount = new Money(0);

        foreach ($lines as $line) {
            if ($line->product->code !== $this->targetCode) {
                continue;
            }

            $pairCount = intdiv($line->quantity, 2);
            $halfPriceCents = intdiv($line->product->unitPrice->cents, 2);
            $discountPerUnit = new Money($line->product->unitPrice->cents - $halfPriceCents);
            $discount = $discount->plus($discountPerUnit->times($pairCount));
        }

        return $discount;
    }
}
