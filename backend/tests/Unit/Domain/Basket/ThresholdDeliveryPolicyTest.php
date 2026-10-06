<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\DeliveryBand;
use App\Domain\Basket\Money;
use App\Domain\Basket\ThresholdDeliveryPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThresholdDeliveryPolicyTest extends TestCase
{
    #[DataProvider('boundaries')]
    public function test_charges_by_the_first_band_that_covers_the_amount(int $amount, int $expectedCharge): void
    {
        $policy = new ThresholdDeliveryPolicy(
            new DeliveryBand(new Money(495), below: new Money(5000)),
            new DeliveryBand(new Money(295), below: new Money(9000)),
            new DeliveryBand(new Money(0)),
        );

        $this->assertSame($expectedCharge, $policy->chargeFor(new Money($amount))->cents);
    }

    /** @return array<string, array{int, int}> */
    public static function boundaries(): array
    {
        return [
            'just under $50' => [4999, 495],
            'exactly $50' => [5000, 295],
            'just under $90' => [8999, 295],
            'exactly $90' => [9000, 0],
        ];
    }

    public function test_requires_an_open_ended_last_band(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ThresholdDeliveryPolicy(new DeliveryBand(new Money(495), below: new Money(5000)));
    }
}
