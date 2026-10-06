<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\Basket;
use App\Domain\Basket\BasketLine;
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
    #[DataProvider('acceptanceExamples')]
    public function test_matches_the_four_challenge_totals(array $codes, string $expected): void
    {
        $basket = $this->basketWith($codes);

        $this->assertSame($expected, $basket->total());
    }

    /** @return array<string, array{list<string>, string}> */
    public static function acceptanceExamples(): array
    {
        return [
            'blue and green' => [['B01', 'G01'], '37.85'],
            'two red' => [['R01', 'R01'], '54.37'],
            'red and green' => [['R01', 'G01'], '60.85'],
            'two blue and three red' => [['B01', 'B01', 'R01', 'R01', 'R01'], '98.27'],
        ];
    }

    /** @param list<string> $codes */
    #[DataProvider('additionalExamples')]
    public function test_handles_empty_single_odd_and_even_quantities(array $codes, string $expected): void
    {
        $basket = $this->basketWith($codes);
        $totals = $basket->breakdown();

        $this->assertSame($expected, $basket->total());
        $this->assertSame($totals->subtotal->cents - $totals->discount->cents, $totals->discountedSubtotal->cents);
        $this->assertSame($totals->discountedSubtotal->cents + $totals->delivery->cents, $totals->total->cents);
    }

    /** @return array<string, array{list<string>, string}> */
    public static function additionalExamples(): array
    {
        return [
            'empty' => [[], '0.00'],
            'one blue' => [['B01'], '12.90'],
            'one green' => [['G01'], '29.90'],
            'one red' => [['R01'], '37.90'],
            'three red' => [array_fill(0, 3, 'R01'), '85.32'],
            'four red' => [array_fill(0, 4, 'R01'), '98.84'],
            'five red' => [array_fill(0, 5, 'R01'), '131.79'],
            'six red' => [array_fill(0, 6, 'R01'), '148.26'],
        ];
    }

    public function test_two_red_receipt_calculates_delivery_after_the_offer(): void
    {
        $totals = $this->basketWith(['R01', 'R01'])->breakdown();

        $this->assertSame(6590, $totals->subtotal->cents);
        $this->assertSame(1648, $totals->discount->cents);
        $this->assertSame(4942, $totals->discountedSubtotal->cents);
        $this->assertSame(495, $totals->delivery->cents);
        $this->assertSame(5437, $totals->total->cents);
        $this->assertCount(1, $totals->lines);
        $this->assertSame('R01', $totals->lines[0]->product->code);
        $this->assertSame(2, $totals->lines[0]->quantity);
        $this->assertSame(3295, $totals->lines[0]->product->unitPrice->cents);
        $this->assertSame(6590, $totals->lines[0]->subtotal()->cents);
    }

    public function test_empty_basket_returns_all_zero_amounts_without_offers_or_delivery(): void
    {
        $basket = $this->basketWith([], [$this->fixedDiscount(100)]);
        $totals = $basket->breakdown();

        $this->assertSame([], $totals->lines);
        $this->assertSame([0, 0, 0, 0, 0], [
            $totals->subtotal->cents,
            $totals->discount->cents,
            $totals->discountedSubtotal->cents,
            $totals->delivery->cents,
            $totals->total->cents,
        ]);
    }

    public function test_invalid_addition_leaves_the_basket_unchanged(): void
    {
        $basket = $this->basketWith(['R01', 'R01']);
        $before = $basket->breakdown();

        try {
            $basket->add('r01');
            $this->fail('A case-mismatched code must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Unknown product code: r01.', $exception->getMessage());
        }

        $after = $basket->breakdown();
        $this->assertSame('54.37', $basket->total());
        $this->assertSame($before->lines[0]->product, $after->lines[0]->product);
        $this->assertSame(2, $after->lines[0]->quantity);
        $this->assertSame(1648, $after->discount->cents);
    }

    public function test_insertion_order_does_not_change_totals_or_line_order(): void
    {
        $codes = ['B01', 'B01', 'R01', 'R01', 'R01', 'G01'];
        $forward = $this->basketWith($codes)->breakdown();
        $backward = $this->basketWith(array_reverse($codes))->breakdown();
        $snapshot = static fn (BasketLine $line): array => [
            $line->product->code, $line->quantity, $line->subtotal()->cents,
        ];

        $this->assertSame($forward->total->cents, $backward->total->cents);
        $this->assertSame($forward->discount->cents, $backward->discount->cents);
        $this->assertSame(array_map($snapshot, $forward->lines), array_map($snapshot, $backward->lines));
    }

    public function test_repeated_calculations_do_not_consume_the_offer_or_accumulate_charges(): void
    {
        $basket = $this->basketWith(['R01', 'R01']);

        $this->assertSame('54.37', $basket->total());
        $this->assertSame(1648, $basket->breakdown()->discount->cents);
        $this->assertSame('54.37', $basket->total());
        $this->assertSame(2, $basket->breakdown()->lines[0]->quantity);
    }

    public function test_can_add_after_calculation_without_changing_previous_snapshots(): void
    {
        $basket = $this->basketWith(['R01', 'R01']);
        $before = $basket->breakdown();

        $basket->add('R01');

        $this->assertSame('85.32', $basket->total());
        $this->assertSame(3, $basket->breakdown()->lines[0]->quantity);
        $this->assertSame(2, $before->lines[0]->quantity);
        $this->assertSame(5437, $before->total->cents);
    }

    public function test_offer_can_be_disabled_through_constructor_injection(): void
    {
        $basket = $this->basketWith(['R01', 'R01'], []);

        $this->assertSame('68.85', $basket->total());
        $this->assertSame(0, $basket->breakdown()->discount->cents);
        $this->assertSame(295, $basket->breakdown()->delivery->cents);
    }

    public function test_uses_an_alternative_catalogue_offer_target_and_delivery_policy(): void
    {
        $catalogue = new ProductCatalogue([
            new Product('P01', 'Purple Widget', new Money(1001)),
        ]);
        $delivery = new ThresholdDeliveryPolicy([
            ['upperBoundCents' => null, 'chargeCents' => 123],
        ]);
        $basket = new Basket($catalogue, $delivery, [new HalfPriceSecondItemOffer('P01')]);

        $basket->add('P01');
        $basket->add('P01');

        $this->assertSame('16.24', $basket->total());
        $this->assertSame(501, $basket->breakdown()->discount->cents);
        $this->assertSame('Purple Widget', $basket->breakdown()->lines[0]->product->name);
    }

    public function test_passes_discounted_merchandise_to_an_injected_delivery_strategy(): void
    {
        $policy = new class implements DeliveryPolicy
        {
            public function chargeFor(Money $discountedSubtotal): Money
            {
                return $discountedSubtotal->cents < 5000 ? new Money(17) : new Money(0);
            }
        };
        $basket = new Basket($this->catalogue(), $policy, [new HalfPriceSecondItemOffer('R01')]);

        $basket->add('R01');
        $basket->add('R01');

        $this->assertSame('49.59', $basket->total());
        $this->assertSame(17, $basket->breakdown()->delivery->cents);
    }

    public function test_sums_independent_offer_discounts(): void
    {
        $basket = $this->basketWith(['R01'], [$this->fixedDiscount(100), $this->fixedDiscount(200)]);

        $this->assertSame(300, $basket->breakdown()->discount->cents);
        $this->assertSame('34.90', $basket->total());
    }

    public function test_rejects_combined_discounts_greater_than_the_subtotal(): void
    {
        $basket = $this->basketWith(['B01'], [$this->fixedDiscount(400), $this->fixedDiscount(396)]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Offer discounts cannot exceed the merchandise subtotal.');

        $basket->total();
    }

    public function test_allows_a_discount_equal_to_the_subtotal(): void
    {
        $basket = $this->basketWith(['B01'], [$this->fixedDiscount(795)]);

        $this->assertSame(0, $basket->breakdown()->discountedSubtotal->cents);
        $this->assertSame('4.95', $basket->total());
    }

    public function test_handles_numeric_string_codes_without_losing_their_identity(): void
    {
        $basket = new Basket(new ProductCatalogue([
            new Product('123', 'Custom Widget', new Money(100)),
        ]), $this->delivery(), []);

        $basket->add('123');

        $this->assertSame('123', $basket->breakdown()->lines[0]->product->code);
        $this->assertSame('5.95', $basket->total());
    }

    #[DataProvider('roundingAlternatives')]
    public function test_rounding_alternatives_explain_the_ambiguity_in_the_examples(string $policy, int $quantity, string $expected): void
    {
        $basket = $this->basketWith(array_fill(0, $quantity, 'R01'), [$this->roundingOffer($policy)]);

        $this->assertSame($expected, $basket->total());
    }

    /** @return array<string, array{string, int, string}> */
    public static function roundingAlternatives(): array
    {
        return [
            'aggregate half-up four' => ['half-up', 4, '98.85'],
            'aggregate half-up six' => ['half-up', 6, '148.27'],
            'per-unit down four' => ['unit-down', 4, '98.84'],
            'per-unit down six' => ['unit-down', 6, '148.26'],
            'aggregate half-even four' => ['half-even', 4, '98.85'],
            'aggregate half-even six' => ['half-even', 6, '148.28'],
        ];
    }

    /** @param list<string> $codes */
    #[DataProvider('acceptanceExamples')]
    public function test_all_three_rounding_policies_match_each_supplied_example(array $codes, string $expected): void
    {
        foreach (['half-up', 'unit-down', 'half-even'] as $policy) {
            $basket = $this->basketWith($codes, [$this->roundingOffer($policy)]);

            $this->assertSame($expected, $basket->total(), $policy);
        }
    }

    /** @param list<string> $codes
     * @param  list<Offer>|null  $offers
     */
    private function basketWith(array $codes, ?array $offers = null): Basket
    {
        $basket = new Basket($this->catalogue(), $this->delivery(), $offers ?? [new HalfPriceSecondItemOffer('R01')]);

        foreach ($codes as $code) {
            $basket->add($code);
        }

        return $basket;
    }

    private function catalogue(): ProductCatalogue
    {
        return new ProductCatalogue([
            new Product('R01', 'Red Widget', new Money(3295)),
            new Product('G01', 'Green Widget', new Money(2495)),
            new Product('B01', 'Blue Widget', new Money(795)),
        ]);
    }

    private function delivery(): ThresholdDeliveryPolicy
    {
        return new ThresholdDeliveryPolicy([
            ['upperBoundCents' => 5000, 'chargeCents' => 495],
            ['upperBoundCents' => 9000, 'chargeCents' => 295],
            ['upperBoundCents' => null, 'chargeCents' => 0],
        ]);
    }

    private function fixedDiscount(int $cents): Offer
    {
        return new class($cents) implements Offer
        {
            public function __construct(private readonly int $cents) {}

            public function discountFor(array $lines): Money
            {
                return new Money($this->cents);
            }
        };
    }

    /** Test-only comparison strategies; production config will use the per-unit offer. */
    private function roundingOffer(string $policy): Offer
    {
        if ($policy === 'unit-down') {
            return new HalfPriceSecondItemOffer('R01');
        }

        return new class($policy) implements Offer
        {
            public function __construct(private readonly string $policy) {}

            public function discountFor(array $lines): Money
            {
                $discount = new Money(0);

                foreach ($lines as $line) {
                    if ($line->product->code !== 'R01') {
                        continue;
                    }

                    $numerator = $line->product->unitPrice->times(intdiv($line->quantity, 2))->cents;
                    $wholeCents = intdiv($numerator, 2);

                    if ($numerator % 2 === 1 && ($this->policy === 'half-up' || $wholeCents % 2 === 1)) {
                        $wholeCents++;
                    }

                    $discount = $discount->plus(new Money($wholeCents));
                }

                return $discount;
            }
        };
    }
}
