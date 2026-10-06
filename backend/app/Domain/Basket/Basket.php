<?php

declare(strict_types=1);

namespace App\Domain\Basket;

final class Basket
{
    /** @var array<string, int> Quantity per product code; prices are read only when totals are calculated. */
    private array $quantities = [];

    /** @param list<Offer> $offers */
    public function __construct(
        private readonly ProductCatalogue $catalogue,
        private readonly DeliveryPolicy $deliveryPolicy,
        private readonly array $offers,
    ) {}

    public function add(string $productCode): void
    {
        $this->catalogue->get($productCode); // Rejects unknown codes before changing state.
        $this->quantities[$productCode] = ($this->quantities[$productCode] ?? 0) + 1;
    }

    public function total(): string
    {
        return $this->breakdown()->total->format();
    }

    public function breakdown(): BasketTotals
    {
        // An empty basket costs nothing, so the minimum delivery charge does not apply.
        if ($this->quantities === []) {
            return new BasketTotals([], new Money(0), new Money(0), new Money(0));
        }

        $lines = [];
        foreach ($this->quantities as $code => $quantity) {
            $lines[] = new BasketLine($this->catalogue->get((string) $code), $quantity);
        }

        $subtotal = array_reduce($lines, fn (Money $sum, BasketLine $line) => $sum->plus($line->subtotal()), new Money(0));
        $discount = array_reduce($this->offers, fn (Money $sum, Offer $offer) => $sum->plus($offer->discountFor($lines)), new Money(0));

        // Delivery is charged on the discounted amount: two reds are $49.42 after the offer, so delivery is $4.95.
        $delivery = $this->deliveryPolicy->chargeFor($subtotal->minus($discount));

        return new BasketTotals($lines, $subtotal, $discount, $delivery);
    }
}
