# Basket design

The basket domain models quantities, product snapshots, offers, and delivery independently of Laravel. All money uses integer USD cents.

## Domain responsibilities

All domain code lives under [`backend/app/Domain/Basket`](../backend/app/Domain/Basket). It uses strict types and contains no Laravel or database dependencies.

| Type | Responsibility |
| --- | --- |
| [`Money`](../backend/app/Domain/Basket/Money.php) | Immutable nonnegative integer USD cents; exact formatting, addition, subtraction, and multiplication. Arithmetic rejects negative results and integer overflow. |
| [`Product`](../backend/app/Domain/Basket/Product.php) | Immutable code, name, and unit price; rejects blank identity fields. |
| [`ProductCatalogue`](../backend/app/Domain/Basket/ProductCatalogue.php) | Immutable products indexed by unique code; rejects duplicates and unknown lookups. |
| [`BasketLine`](../backend/app/Domain/Basket/BasketLine.php) | Immutable product/positive-quantity snapshot; computes its gross subtotal. |
| [`Offer`](../backend/app/Domain/Basket/Offer.php) | Calculates a discount from typed line snapshots. |
| [`HalfPriceSecondItemOffer`](../backend/app/Domain/Basket/HalfPriceSecondItemOffer.php) | Repeating pairs for an injected code; reads the snapshot price and rounds each half-price unit down to cents. |
| [`DeliveryPolicy`](../backend/app/Domain/Basket/DeliveryPolicy.php) | Calculates delivery from discounted merchandise. |
| [`ThresholdDeliveryPolicy`](../backend/app/Domain/Basket/ThresholdDeliveryPolicy.php) | Validates ordered, increasing exclusive bounds, one final unbounded band, and nonnegative charges. Bands use documented array shapes. |
| [`BasketTotals`](../backend/app/Domain/Basket/BasketTotals.php) | Immutable lines and amounts; derives discounted subtotal and final total from gross, discount, and delivery. |
| [`Basket`](../backend/app/Domain/Basket/Basket.php) | Owns quantities and coordinates the injected catalogue, offers, and delivery policy. |

`Basket` is the only mutable domain object. Each `add()` adds one unit after validating the code. A quote copies quantities into line snapshots, sorted by code, sums gross and offer discounts, rejects excess discounts, and calculates delivery from the net amount. Empty baskets bypass offers and delivery. Quoting never consumes a promotion or changes quantities; old breakdowns remain unchanged after additions.

## Direct construction

This ordinary PHP example needs Composer autoloading, without booting Laravel:

```php
require 'backend/vendor/autoload.php';

use App\Domain\Basket\Basket;
use App\Domain\Basket\HalfPriceSecondItemOffer;
use App\Domain\Basket\Money;
use App\Domain\Basket\Product;
use App\Domain\Basket\ProductCatalogue;
use App\Domain\Basket\ThresholdDeliveryPolicy;

$catalogue = new ProductCatalogue([
    new Product('R01', 'Red Widget', new Money(3295)),
    new Product('G01', 'Green Widget', new Money(2495)),
    new Product('B01', 'Blue Widget', new Money(795)),
]);
$delivery = new ThresholdDeliveryPolicy([
    ['upperBoundCents' => 5000, 'chargeCents' => 495],
    ['upperBoundCents' => 9000, 'chargeCents' => 295],
    ['upperBoundCents' => null, 'chargeCents' => 0],
]);
$basket = new Basket($catalogue, $delivery, [new HalfPriceSecondItemOffer('R01')]);
$basket->add('R01');
$basket->add('R01');
echo $basket->total(); // "54.37"
$totals = $basket->breakdown(); // Immutable Money amounts and gross lines.
```

`total(): string` is an interface choice: the challenge does not prescribe its return type. The string always contains two decimal places and never passes through a float. Laravel shares the immutable catalogue and stateless quotation service. The service creates a fresh mutable basket for each quotation; baskets are never bound as singletons.

## Two-red and six-red walkthrough

Half of 3295 cents contains a fractional cent. The chosen policy prices each eligible second unit at `intdiv(3295, 2) = 1647` cents. Its discount is `3295 - 1647 = 1648` cents.

| Amount | Two R01 | Six R01 |
| --- | ---: | ---: |
| Full-price units | 1 × $32.95 | 3 × $32.95 |
| Half-price units | 1 × $16.47 | 3 × $16.47 |
| Gross merchandise | $65.90 | $197.70 |
| Savings | $16.48 | $49.44 |
| Discounted merchandise | $49.42 | $148.26 |
| Delivery | $4.95 | $0.00 |
| **Total** | **$54.37** | **$148.26** |

Delivery follows offers. In the two-red case, using gross merchandise for delivery would produce $52.37, whereas the challenge requires $54.37. The discounted subtotal therefore determines the delivery band.

## Rounding alternatives

All three policies reproduce every supplied example. They diverge beyond one discounted unit:

| Policy | Four R01 | Six R01 |
| --- | ---: | ---: |
| Round the aggregate discount half-up | $98.85 | $148.27 |
| **Chosen: each half-price unit rounded down** | **$98.84** | **$148.26** |
| Round the aggregate discount half-even | $98.85 | $148.28 |

The selected rule makes the same discounted unit price repeat consistently. It is equivalent to rounding each unit's discount half-up and then multiplying. Half-up itself is not incorrect; its stage and unit of calculation matter. The test suite injects test-only alternative offers to verify this table and their agreement with the four challenge examples. Production has only the selected offer.

Whole-cent unit prices explain the receipt. They do not define how returns are allocated or whether a promotion is recalculated after a refund.

## Assumptions and boundaries

- Repeating red pairs, one unmatched red at full price, per-unit rounding down, and an all-zero empty basket are assumptions.
- Codes are case-sensitive; an invalid addition leaves quantities unchanged. Prices stay fixed for the basket lifetime.
- USD only, no taxes, and basket quotation only. Returns, refunds, persistence, checkout, payment, inventory, and accounts are out of scope.
- Independent offer discounts are summed and may not exceed gross merchandise. Overlap priorities and best-offer selection are not specified.
- For a nonempty basket with zero net merchandise, the injected policy still applies; the configured threshold policy charges $4.95. Only an empty basket bypasses delivery.
- The API merges duplicate product rows before quotation. It validates catalogue membership, strict integer quantities, JSON containers, and a total limit of 1000 units. Requests do not share basket contents.


## Framework integration

[`config/basket.php`](../backend/config/basket.php) contains primitive product prices, delivery bands, and the offer target/description. [`BasketServiceProvider`](../backend/app/Providers/BasketServiceProvider.php) constructs immutable products and stateless policies, so Laravel configuration remains cacheable.

[`QuoteBasket`](../backend/app/Application/QuoteBasket.php) merges duplicate rows and constructs a new `Basket` for every call. [`QuoteBasketRequest`](../backend/app/Http/Requests/QuoteBasketRequest.php) handles the HTTP boundary: malformed JSON returns 400, invalid fields or containers return 422, and valid empty baskets return zero amounts. The controller serializes the domain's breakdown without recalculating pricing.

React owns local quantities and displays authoritative server amounts. A quote belongs to a particular basket and retry attempt. Cancellation and request counters prevent an old success or error from replacing a newer result. Amounts are hidden while the current basket has no matching successful quote.

## Tests

The [pure domain tests](../backend/tests/Unit/Domain/Basket) exercise money, delivery boundaries, all supplied totals, quantities through six reds, alternative rounding policies, constructor injection, and state preservation without booting Laravel. [API feature tests](../backend/tests/Feature/BasketApiTest.php) cover validation, duplicate rows, aggregate limits, alternate configuration, and request isolation without a database. See [verification](verification.md) for the executed checks.
