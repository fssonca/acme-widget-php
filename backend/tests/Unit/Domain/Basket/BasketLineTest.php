<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\BasketLine;
use App\Domain\Basket\Money;
use App\Domain\Basket\Product;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BasketLineTest extends TestCase
{
    public function test_reports_the_gross_subtotal_from_the_snapshot_price(): void
    {
        $product = new Product('R01', 'Red Widget', new Money(3295));
        $line = new BasketLine($product, 3);

        $this->assertSame($product, $line->product);
        $this->assertSame(3, $line->quantity);
        $this->assertSame(9885, $line->subtotal()->cents);
    }

    #[DataProvider('invalidQuantities')]
    public function test_rejects_nonpositive_quantities(int $quantity): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BasketLine(new Product('B01', 'Blue Widget', new Money(795)), $quantity);
    }

    /** @return array<string, array{int}> */
    public static function invalidQuantities(): array
    {
        return ['zero' => [0], 'negative' => [-1]];
    }
}
