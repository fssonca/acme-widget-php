<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

final readonly class ProductCatalogue
{
    /** @var array<string, Product> */
    private array $products;

    public function __construct(Product ...$products)
    {
        $indexed = [];

        foreach ($products as $product) {
            if (isset($indexed[$product->code])) {
                throw new InvalidArgumentException("Duplicate product code: {$product->code}.");
            }

            $indexed[$product->code] = $product;
        }

        $this->products = $indexed;
    }

    public function get(string $code): Product
    {
        return $this->products[$code] ?? throw new InvalidArgumentException("Unknown product code: {$code}.");
    }

    /** @return list<Product> */
    public function all(): array
    {
        return array_values($this->products);
    }
}
