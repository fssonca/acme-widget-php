<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\QuoteBasket;
use App\Domain\Basket\DeliveryPolicy;
use App\Domain\Basket\HalfPriceSecondItemOffer;
use App\Domain\Basket\Money;
use App\Domain\Basket\Product;
use App\Domain\Basket\ProductCatalogue;
use App\Domain\Basket\ThresholdDeliveryPolicy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

final class BasketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProductCatalogue::class, function (): ProductCatalogue {
            /** @var list<array{code: string, name: string, unitPriceCents: int}> $products */
            $products = Config::array('basket.products');

            return new ProductCatalogue(array_map(
                static fn (array $product): Product => new Product(
                    $product['code'], $product['name'], new Money($product['unitPriceCents']),
                ),
                $products,
            ));
        });

        $this->app->singleton(DeliveryPolicy::class, function (): DeliveryPolicy {
            /** @var list<array{upperBoundCents: int|null, chargeCents: int}> $bands */
            $bands = Config::array('basket.delivery');

            return new ThresholdDeliveryPolicy($bands);
        });

        $this->app->singleton(QuoteBasket::class, static fn (Application $app): QuoteBasket => new QuoteBasket(
            $app->make(ProductCatalogue::class),
            $app->make(DeliveryPolicy::class),
            [new HalfPriceSecondItemOffer(Config::string('basket.offer.targetCode'))],
        ));
    }
}
