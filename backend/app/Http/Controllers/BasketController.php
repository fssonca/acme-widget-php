<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Basket\Basket;
use App\Domain\Basket\BasketLine;
use App\Domain\Basket\Offer;
use App\Domain\Basket\Product;
use App\Domain\Basket\ProductCatalogue;
use App\Http\Requests\QuoteBasketRequest;
use Illuminate\Container\Attributes\Tag;
use Illuminate\Http\JsonResponse;

final class BasketController
{
    /** @param iterable<Offer> $offers */
    public function catalogue(ProductCatalogue $catalogue, #[Tag('basket.offers')] iterable $offers): JsonResponse
    {
        return response()->json([
            'products' => array_map(fn (Product $product) => [
                'code' => $product->code,
                'name' => $product->name,
                'unitPriceCents' => $product->unitPrice->cents,
            ], $catalogue->all()),
            'offers' => array_map(fn (Offer $offer) => $offer->description(), [...$offers]),
        ]);
    }

    // The basket is the server-side source of truth; the client only sends codes and quantities.
    public function quote(QuoteBasketRequest $request, Basket $basket): JsonResponse
    {
        foreach ($request->quantities() as $code => $quantity) {
            // The challenge's Basket::add() takes one unit per call; validation caps quantities at 99.
            for ($unit = 0; $unit < $quantity; $unit++) {
                $basket->add((string) $code);
            }
        }

        $totals = $basket->breakdown();

        return response()->json([
            'items' => array_map(fn (BasketLine $line) => [
                'code' => $line->product->code,
                'quantity' => $line->quantity,
                'lineSubtotalCents' => $line->subtotal()->cents,
            ], $totals->lines),
            'subtotalCents' => $totals->subtotal->cents,
            'discountCents' => $totals->discount->cents,
            'deliveryCents' => $totals->delivery->cents,
            'totalCents' => $totals->total->cents,
        ]);
    }
}
