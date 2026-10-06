<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\QuoteBasket;
use App\Domain\Basket\BasketLine;
use App\Domain\Basket\Product;
use App\Domain\Basket\ProductCatalogue;
use App\Http\Requests\QuoteBasketRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;

final class BasketController extends Controller
{
    public function catalogue(ProductCatalogue $catalogue): JsonResponse
    {
        return response()->json([
            'currency' => 'USD',
            'products' => array_map(static fn (Product $product): array => [
                'code' => $product->code,
                'name' => $product->name,
                'unitPriceCents' => $product->unitPrice->cents,
            ], $catalogue->all()),
            'offerDescription' => Config::string('basket.offer.description'),
        ]);
    }

    public function quote(QuoteBasketRequest $request, QuoteBasket $quoteBasket): JsonResponse
    {
        $totals = $quoteBasket->quote($request->items());

        return response()->json([
            'currency' => 'USD',
            'items' => array_map(static fn (BasketLine $line): array => [
                'code' => $line->product->code,
                'name' => $line->product->name,
                'quantity' => $line->quantity,
                'unitPriceCents' => $line->product->unitPrice->cents,
                'lineSubtotalCents' => $line->subtotal()->cents,
            ], $totals->lines),
            'subtotalCents' => $totals->subtotal->cents,
            'discountCents' => $totals->discount->cents,
            'discountedSubtotalCents' => $totals->discountedSubtotal->cents,
            'deliveryCents' => $totals->delivery->cents,
            'totalCents' => $totals->total->cents,
        ]);
    }
}
