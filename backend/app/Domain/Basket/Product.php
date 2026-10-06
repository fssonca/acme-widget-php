<?php

declare(strict_types=1);

namespace App\Domain\Basket;

final readonly class Product
{
    public function __construct(
        public string $code,
        public string $name,
        public Money $unitPrice,
    ) {}
}
