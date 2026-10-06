<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use App\Domain\Basket\Money;
use App\Domain\Basket\Product;
use App\Domain\Basket\ProductCatalogue;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProductCatalogueTest extends TestCase
{
    public function test_looks_up_products_by_code(): void
    {
        $red = new Product('R01', 'Red Widget', new Money(3295));
        $blue = new Product('B01', 'Blue Widget', new Money(795));
        $catalogue = new ProductCatalogue($red, $blue);

        $this->assertSame($blue, $catalogue->get('B01'));
        $this->assertSame([$red, $blue], $catalogue->all());
    }

    public function test_rejects_unknown_codes(): void
    {
        $this->expectExceptionMessage('Unknown product code: r01.');

        (new ProductCatalogue(new Product('R01', 'Red Widget', new Money(3295))))->get('r01');
    }

    public function test_rejects_duplicate_codes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProductCatalogue(
            new Product('R01', 'Red Widget', new Money(3295)),
            new Product('R01', 'Another Red Widget', new Money(1000)),
        );
    }
}
