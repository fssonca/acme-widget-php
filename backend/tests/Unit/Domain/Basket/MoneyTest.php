<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[DataProvider('amounts')]
    public function test_formats_cents_as_a_decimal_string(int $cents, string $expected): void
    {
        $this->assertSame($expected, (new Money($cents))->format());
    }

    /** @return array<string, array{int, string}> */
    public static function amounts(): array
    {
        return [
            'zero' => [0, '0.00'],
            'one cent' => [1, '0.01'],
            'ten cents' => [10, '0.10'],
            'red widget' => [3295, '32.95'],
        ];
    }

    public function test_arithmetic_returns_new_values(): void
    {
        $money = new Money(795);

        $this->assertSame(3290, $money->plus(new Money(2495))->cents);
        $this->assertSame(700, $money->minus(new Money(95))->cents);
        $this->assertSame(1590, $money->times(2)->cents);
        $this->assertSame(795, $money->cents);
    }

    public function test_rejects_negative_results(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(795))->minus(new Money(796));
    }
}
