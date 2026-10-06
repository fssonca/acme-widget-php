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
    public function test_looks_up_products_and_preserves_string_codes(): void
    {
        $red = new Product('R01', 'Red Widget', new Money(3295));
        $numeric = new Product('123', 'Custom Widget', new Money(10));
        $catalogue = new ProductCatalogue([$red, $numeric]);

        $this->assertSame($red, $catalogue->get('R01'));
        $this->assertSame($numeric, $catalogue->get('123'));
        $this->assertSame(['R01', '123'], $catalogue->codes());
        $this->assertSame([$red, $numeric], $catalogue->all());
    }

    public function test_rejects_duplicate_codes_even_when_prices_differ(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProductCatalogue([
            new Product('R01', 'Red Widget', new Money(3295)),
            new Product('R01', 'Other Red Widget', new Money(1000)),
        ]);
    }

    public function test_distinguishes_codes_with_different_case(): void
    {
        $upper = new Product('R01', 'Red Widget', new Money(3295));
        $lower = new Product('r01', 'Other Widget', new Money(100));
        $catalogue = new ProductCatalogue([$upper, $lower]);

        $this->assertSame($upper, $catalogue->get('R01'));
        $this->assertSame($lower, $catalogue->get('r01'));
    }

    public function test_rejects_unknown_codes(): void
    {
        $catalogue = new ProductCatalogue([
            new Product('R01', 'Red Widget', new Money(3295)),
        ]);
        $this->expectException(InvalidArgumentException::class);

        $catalogue->get('r01');
    }

    public function test_empty_catalogue_has_no_products(): void
    {
        $catalogue = new ProductCatalogue([]);

        $this->assertSame([], $catalogue->all());
        $this->assertSame([], $catalogue->codes());
    }
}
