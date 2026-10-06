<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Basket\Product;
use App\Domain\Basket\ProductCatalogue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class QuoteBasketRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(ProductCatalogue $catalogue): array
    {
        $codes = array_map(fn (Product $product) => $product->code, $catalogue->all());

        return [
            'items' => ['present', 'list'],
            'items.*.code' => ['required', 'string', 'distinct', Rule::in($codes)],
            'items.*.quantity' => ['required', 'integer:strict', 'between:1,99'],
        ];
    }

    /** @return array<string, int> Quantity per product code. */
    public function quantities(): array
    {
        /** @var list<array{code: string, quantity: int}> $items */
        $items = $this->validated('items');

        return array_column($items, 'quantity', 'code');
    }
}
