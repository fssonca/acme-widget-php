<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Basket\Basket;
use App\Domain\Basket\DeliveryBand;
use App\Domain\Basket\DeliveryPolicy;
use App\Domain\Basket\HalfPriceSecondItemOffer;
use App\Domain\Basket\Money;
use App\Domain\Basket\Offer;
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

        // Every offer tagged 'basket.offers' is applied to baskets and listed in the catalogue; register new offers here.
        $this->app->singleton(HalfPriceSecondItemOffer::class, fn () => new HalfPriceSecondItemOffer(
            Config::string('basket.offer.productCode'),
            Config::string('basket.offer.description'),
        ));
        $this->app->tag([HalfPriceSecondItemOffer::class], 'basket.offers');

        // bind(), not singleton(): every request gets a new, empty basket.
        $this->app->bind(Basket::class, function (Application $app): Basket {
            /** @var list<Offer> $offers */
            $offers = [...$app->tagged('basket.offers')];

            return new Basket($app->make(ProductCatalogue::class), $app->make(DeliveryPolicy::class), $offers);
        });
    }
}
