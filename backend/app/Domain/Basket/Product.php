<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;

final readonly class Product
{
    public function __construct(
        public string $code,
        public string $name,
        public Money $unitPrice,
    ) {
        if (trim($code) === '' || trim($name) === '') {
            throw new InvalidArgumentException('Product code and name must not be blank.');
        }
    }
}
