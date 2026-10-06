<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

final class Basket
{
    /** @var array<string, int> */
    private array $quantities = [];

    /** @param list<Offer> $offers */
    public function __construct(
        private readonly ProductCatalogue $catalogue,
        private readonly DeliveryPolicy $deliveryPolicy,
        private readonly array $offers,
    ) {}

    public function add(string $productCode): void
    {
        $this->catalogue->get($productCode);
        $quantity = $this->quantities[$productCode] ?? 0;

        if ($quantity === PHP_INT_MAX) {
            throw new InvalidArgumentException('Basket quantity exceeds the integer range.');
        }

        $this->quantities[$productCode] = $quantity + 1;
    }

    public function total(): string
    {
        return $this->breakdown()->total->format();
    }

    public function breakdown(): BasketTotals
    {
        if ($this->quantities === []) {
            return new BasketTotals([], new Money(0), new Money(0), new Money(0));
        }

        $quantities = $this->quantities;
        ksort($quantities, SORT_STRING);

        $lines = [];
        $subtotal = new Money(0);

        foreach ($quantities as $code => $quantity) {
            $line = new BasketLine($this->catalogue->get((string) $code), $quantity);
            $lines[] = $line;
            $subtotal = $subtotal->plus($line->subtotal());
        }

        $discount = new Money(0);

        foreach ($this->offers as $offer) {
            $discount = $discount->plus($offer->discountFor($lines));
        }

        if ($discount->cents > $subtotal->cents) {
            throw new InvalidArgumentException('Offer discounts cannot exceed the merchandise subtotal.');
        }

        $discountedSubtotal = $subtotal->minus($discount);
        $delivery = $this->deliveryPolicy->chargeFor($discountedSubtotal);

        return new BasketTotals($lines, $subtotal, $discount, $delivery);
    }
}
