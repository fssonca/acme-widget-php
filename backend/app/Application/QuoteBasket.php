<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Basket\Basket;
use App\Domain\Basket\BasketTotals;
use App\Domain\Basket\DeliveryPolicy;
use App\Domain\Basket\Offer;
use App\Domain\Basket\ProductCatalogue;

final readonly class QuoteBasket
{
    /** @param list<Offer> $offers */
    public function __construct(
        private ProductCatalogue $catalogue,
        private DeliveryPolicy $deliveryPolicy,
        private array $offers,
    ) {}

    /** @param list<array{code: string, quantity: int}> $items */
    public function quote(array $items): BasketTotals
    {
        $quantities = [];

        foreach ($items as $item) {
            $quantities[$item['code']] = ($quantities[$item['code']] ?? 0) + $item['quantity'];
        }

        $basket = new Basket($this->catalogue, $this->deliveryPolicy, $this->offers);

        foreach ($quantities as $code => $quantity) {
            for ($unit = 0; $unit < $quantity; $unit++) {
                $basket->add((string) $code);
            }
        }

        return $basket->breakdown();
    }
}
