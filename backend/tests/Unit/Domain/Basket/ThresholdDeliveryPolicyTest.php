<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\Money;
use App\Domain\Basket\ThresholdDeliveryPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThresholdDeliveryPolicyTest extends TestCase
{
    #[DataProvider('deliveryBoundaries')]
    public function test_uses_exclusive_upper_bounds(int $subtotal, int $delivery): void
    {
        $policy = new ThresholdDeliveryPolicy([
            ['upperBoundCents' => 5000, 'chargeCents' => 495],
            ['upperBoundCents' => 9000, 'chargeCents' => 295],
            ['upperBoundCents' => null, 'chargeCents' => 0],
        ]);

        $this->assertSame($delivery, $policy->chargeFor(new Money($subtotal))->cents);
    }

    /** @return array<string, array{int, int}> */
    public static function deliveryBoundaries(): array
    {
        return [
            'zero merchandise' => [0, 495],
            'below fifty' => [4999, 495],
            'exactly fifty' => [5000, 295],
            'below ninety' => [8999, 295],
            'exactly ninety' => [9000, 0],
            'above ninety' => [9001, 0],
            'largest amount' => [PHP_INT_MAX, 0],
        ];
    }

    public function test_accepts_a_single_unbounded_band(): void
    {
        $policy = new ThresholdDeliveryPolicy([
            ['upperBoundCents' => null, 'chargeCents' => 123],
        ]);

        $this->assertSame(123, $policy->chargeFor(new Money(50000))->cents);
    }

    /** @param array<int, array{upperBoundCents: int|null, chargeCents: int}> $bands */
    #[DataProvider('invalidBands')]
    public function test_rejects_invalid_delivery_configuration(array $bands): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ThresholdDeliveryPolicy($bands);
    }

    /** @return array<string, array{array<int, array{upperBoundCents: int|null, chargeCents: int}>}> */
    public static function invalidBands(): array
    {
        return [
            'no bands' => [[]],
            'no final unbounded band' => [[['upperBoundCents' => 5000, 'chargeCents' => 495]]],
            'decreasing bounds' => [[
                ['upperBoundCents' => 9000, 'chargeCents' => 495],
                ['upperBoundCents' => 5000, 'chargeCents' => 295],
                ['upperBoundCents' => null, 'chargeCents' => 0],
            ]],
            'repeated bounds' => [[
                ['upperBoundCents' => 5000, 'chargeCents' => 495],
                ['upperBoundCents' => 5000, 'chargeCents' => 295],
                ['upperBoundCents' => null, 'chargeCents' => 0],
            ]],
            'unbounded before the end' => [[
                ['upperBoundCents' => null, 'chargeCents' => 495],
                ['upperBoundCents' => 5000, 'chargeCents' => 295],
                ['upperBoundCents' => null, 'chargeCents' => 0],
            ]],
            'two unbounded bands' => [[
                ['upperBoundCents' => null, 'chargeCents' => 495],
                ['upperBoundCents' => null, 'chargeCents' => 0],
            ]],
            'negative charge' => [[['upperBoundCents' => null, 'chargeCents' => -1]]],
            'zero bound' => [[
                ['upperBoundCents' => 0, 'chargeCents' => 495],
                ['upperBoundCents' => null, 'chargeCents' => 0],
            ]],
            'negative bound' => [[
                ['upperBoundCents' => -1, 'chargeCents' => 495],
                ['upperBoundCents' => null, 'chargeCents' => 0],
            ]],
            'nonconsecutive keys' => [[2 => ['upperBoundCents' => null, 'chargeCents' => 0]]],
        ];
    }
}
