<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\BasketLine;
use App\Domain\Basket\HalfPriceSecondItemOffer;
use App\Domain\Basket\Money;
use App\Domain\Basket\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HalfPriceSecondItemOfferTest extends TestCase
{
    #[DataProvider('redQuantities')]
    public function test_discounts_every_complete_pair(int $quantity, int $expectedDiscount): void
    {
        $line = new BasketLine(new Product('R01', 'Red Widget', new Money(3295)), $quantity);

        $this->assertSame($expectedDiscount, (new HalfPriceSecondItemOffer('R01'))->discountFor([$line])->cents);
    }

    /** @return array<string, array{int, int}> */
    public static function redQuantities(): array
    {
        return [
            'one' => [1, 0],
            'two' => [2, 1648],
            'three' => [3, 1648],
            'four' => [4, 3296],
        ];
    }

    public function test_rounds_each_half_price_unit_down_to_whole_cents(): void
    {
        $line = new BasketLine(new Product('X01', 'Odd Widget', new Money(1001)), 2);

        // The half-price unit costs 500 cents (500.5 rounded down), so the saving is 501.
        $this->assertSame(501, (new HalfPriceSecondItemOffer('X01'))->discountFor([$line])->cents);
    }

    public function test_ignores_other_products(): void
    {
        $line = new BasketLine(new Product('G01', 'Green Widget', new Money(2495)), 2);

        $this->assertSame(0, (new HalfPriceSecondItemOffer('R01'))->discountFor([$line])->cents);
    }
}
