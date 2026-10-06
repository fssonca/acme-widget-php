<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\Basket;
use App\Domain\Basket\DeliveryBand;
use App\Domain\Basket\DeliveryPolicy;
use App\Domain\Basket\HalfPriceSecondItemOffer;
use App\Domain\Basket\Money;
use App\Domain\Basket\Offer;
use App\Domain\Basket\Product;
use App\Domain\Basket\ProductCatalogue;
use App\Domain\Basket\ThresholdDeliveryPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BasketTest extends TestCase
{
    /** @param list<string> $codes */
    #[DataProvider('challengeExamples')]
    public function test_matches_the_challenge_examples(array $codes, string $expectedTotal): void
    {
        $this->assertSame($expectedTotal, $this->basketWith($codes)->total());
    }

    /** @return array<string, array{list<string>, string}> */
    public static function challengeExamples(): array
    {
        return [
            'B01, G01' => [['B01', 'G01'], '37.85'],
            'R01, R01' => [['R01', 'R01'], '54.37'],
            'R01, G01' => [['R01', 'G01'], '60.85'],
            'B01, B01, R01, R01, R01' => [['B01', 'B01', 'R01', 'R01', 'R01'], '98.27'],
        ];
    }

    public function test_charges_delivery_on_the_subtotal_after_offers(): void
    {
        $totals = $this->basketWith(['R01', 'R01'])->breakdown();

        // $65.90 - $16.48 = $49.42, which is under $50, so delivery is $4.95 (not $2.95 on the gross $65.90).
        $this->assertSame([6590, 1648, 495, 5437], [
            $totals->subtotal->cents, $totals->discount->cents, $totals->delivery->cents, $totals->total->cents,
        ]);
    }

    public function test_rounds_per_half_price_unit_where_the_examples_are_ambiguous(): void
    {
        // Rounding the combined $32.95 discount half-up would give $98.85; per unit it is 2 x $16.48 off.
        $this->assertSame('98.84', $this->basketWith(['R01', 'R01', 'R01', 'R01'])->total());
    }

    public function test_empty_basket_costs_nothing(): void
    {
        $this->assertSame('0.00', $this->basketWith([])->total());
    }

    public function test_rejects_unknown_codes_without_changing_the_basket(): void
    {
        $basket = $this->basketWith(['R01']);

        try {
            $basket->add('X99');
            $this->fail('Unknown codes must be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertSame('37.90', $basket->total());
        }
    }

    public function test_earlier_breakdowns_are_not_changed_by_later_additions(): void
    {
        $basket = $this->basketWith(['R01', 'R01']);
        $before = $basket->breakdown();

        $basket->add('R01');

        $this->assertSame(5437, $before->total->cents);
        $this->assertSame('85.32', $basket->total());
    }

    public function test_offers_and_delivery_are_injected_strategies(): void
    {
        $tenPercentOff = new class implements Offer
        {
            public function discountFor(array $lines): Money
            {
                $subtotal = array_sum(array_map(fn ($line) => $line->subtotal()->cents, $lines));

                return new Money(intdiv($subtotal, 10));
            }

            public function description(): string
            {
                return '10% off everything';
            }
        };
        $flatDelivery = new class implements DeliveryPolicy
        {
            public function chargeFor(Money $discountedSubtotal): Money
            {
                return new Money(100);
            }
        };
        $basket = new Basket($this->catalogue(), $flatDelivery, [$tenPercentOff]);
        $basket->add('G01');

        $this->assertSame('23.46', $basket->total()); // $24.95 - $2.49 + $1.00
    }

    /** @param list<string> $codes */
    private function basketWith(array $codes): Basket
    {
        $delivery = new ThresholdDeliveryPolicy(
            new DeliveryBand(new Money(495), below: new Money(5000)),
            new DeliveryBand(new Money(295), below: new Money(9000)),
            new DeliveryBand(new Money(0)),
        );
        $basket = new Basket($this->catalogue(), $delivery, [new HalfPriceSecondItemOffer('R01', 'Half-price reds')]);

        foreach ($codes as $code) {
            $basket->add($code);
        }

        return $basket;
    }

    private function catalogue(): ProductCatalogue
    {
        return new ProductCatalogue(
            new Product('R01', 'Red Widget', new Money(3295)),
            new Product('G01', 'Green Widget', new Money(2495)),
            new Product('B01', 'Blue Widget', new Money(795)),
        );
    }
}
