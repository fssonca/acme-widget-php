<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\Money;
use App\Domain\Basket\Product;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    #[DataProvider('blankFields')]
    public function test_rejects_blank_product_identity(string $code, string $name): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Product($code, $name, new Money(795));
    }

    /** @return array<string, array{string, string}> */
    public static function blankFields(): array
    {
        return [
            'empty code' => ['', 'Blue Widget'],
            'whitespace code' => [' ', 'Blue Widget'],
            'empty name' => ['B01', ''],
            'whitespace name' => ['B01', ' '],
        ];
    }

    public function test_preserves_case_sensitive_identity_and_zero_price(): void
    {
        $product = new Product('b01', 'Complimentary Blue Widget', new Money(0));

        $this->assertSame('b01', $product->code);
        $this->assertSame('Complimentary Blue Widget', $product->name);
        $this->assertSame(0, $product->unitPrice->cents);
    }
}
