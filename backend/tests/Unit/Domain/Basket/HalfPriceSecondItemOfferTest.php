<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\BasketLine;
use App\Domain\Basket\HalfPriceSecondItemOffer;
use App\Domain\Basket\Money;
use App\Domain\Basket\Product;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HalfPriceSecondItemOfferTest extends TestCase
{
    #[DataProvider('redQuantities')]
    public function test_repeats_the_same_discount_for_each_complete_pair(int $quantity, int $discount): void
    {
        $offer = new HalfPriceSecondItemOffer('R01');
        $line = new BasketLine(new Product('R01', 'Red Widget', new Money(3295)), $quantity);

        $this->assertSame($discount, $offer->discountFor([$line])->cents);
    }

    /** @return array<string, array{int, int}> */
    public static function redQuantities(): array
    {
        return [
            'one red' => [1, 0],
            'two red' => [2, 1648],
            'three red' => [3, 1648],
            'four red' => [4, 3296],
            'five red' => [5, 3296],
            'six red' => [6, 4944],
        ];
    }

    #[DataProvider('snapshotPrices')]
    public function test_uses_the_line_price_and_rounds_each_half_price_down(int $unitPrice, int $discount): void
    {
        $offer = new HalfPriceSecondItemOffer('R01');
        $line = new BasketLine(new Product('R01', 'Repriced Red Widget', new Money($unitPrice)), 2);

        $this->assertSame($discount, $offer->discountFor([$line])->cents);
    }

    /** @return array<string, array{int, int}> */
    public static function snapshotPrices(): array
    {
        return [
            'odd cents' => [1001, 501],
            'even cents' => [1000, 500],
            'one cent' => [1, 1],
            'free product' => [0, 0],
        ];
    }

    public function test_applies_to_an_injected_target_and_ignores_other_products(): void
    {
        $offer = new HalfPriceSecondItemOffer('G01');
        $lines = [
            new BasketLine(new Product('R01', 'Red Widget', new Money(3295)), 6),
            new BasketLine(new Product('G01', 'Green Widget', new Money(2495)), 2),
        ];

        $this->assertSame(1248, $offer->discountFor($lines)->cents);
        $this->assertSame(0, $offer->discountFor([])->cents);
    }

    public function test_rejects_blank_target_codes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HalfPriceSecondItemOffer(' ');
    }
}
