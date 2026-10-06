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
    public function test_formats_exact_decimal_strings(int $cents, string $expected): void
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
            'whole dollar' => [100, '1.00'],
            'discounted red' => [1647, '16.47'],
            'red' => [3295, '32.95'],
            'beyond float precision' => [PHP_INT_MAX, '92233720368547758.07'],
        ];
    }

    public function test_arithmetic_returns_new_values_without_changing_inputs(): void
    {
        $money = new Money(795);

        $this->assertSame(3290, $money->plus(new Money(2495))->cents);
        $this->assertSame(700, $money->minus(new Money(95))->cents);
        $this->assertSame(1590, $money->times(2)->cents);
        $this->assertSame(0, $money->times(0)->cents);
        $this->assertSame(795, $money->cents);
    }

    public function test_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Money(-1);
    }

    public function test_rejects_subtraction_below_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(795))->minus(new Money(796));
    }

    public function test_rejects_negative_multipliers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(795))->times(-1);
    }

    public function test_rejects_addition_overflow_before_it_can_become_a_float(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(PHP_INT_MAX))->plus(new Money(1));
    }

    public function test_rejects_multiplication_overflow_before_it_can_become_a_float(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(PHP_INT_MAX))->times(2);
    }
}
