<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Basket\Basket;
use App\Domain\Basket\DeliveryBand;
use App\Domain\Basket\DeliveryPolicy;
use App\Domain\Basket\HalfPriceSecondItemOffer;
use App\Domain\Basket\Money;
use App\Domain\Basket\Product;
use App\Domain\Basket\ProductCatalogue;
use App\Domain\Basket\ThresholdDeliveryPolicy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

// Builds the framework-free domain objects from config/basket.php.
final class BasketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProductCatalogue::class, function (): ProductCatalogue {
            /** @var list<array{code: string, name: string, unitPriceCents: int}> $products */
            $products = Config::array('basket.products');

            return new ProductCatalogue(...array_map(
                fn (array $product) => new Product($product['code'], $product['name'], new Money($product['unitPriceCents'])),
                $products,
            ));
        });

        $this->app->singleton(DeliveryPolicy::class, function (): DeliveryPolicy {
            /** @var list<array{belowCents: int|null, chargeCents: int}> $bands */
            $bands = Config::array('basket.delivery');

            return new ThresholdDeliveryPolicy(...array_map(
                fn (array $band) => new DeliveryBand(
                    new Money($band['chargeCents']),
                    $band['belowCents'] === null ? null : new Money($band['belowCents']),
                ),
                $bands,
            ));
        });

        // bind(), not singleton(): every request gets a new, empty basket. New offers are registered here.
        $this->app->bind(Basket::class, fn (Application $app) => new Basket(
            $app->make(ProductCatalogue::class),
            $app->make(DeliveryPolicy::class),
            [new HalfPriceSecondItemOffer(Config::string('basket.offer.productCode'))],
        ));
    }
}
