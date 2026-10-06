# Acme Widget Co

A proof-of-concept sales basket: a small, framework-free PHP pricing model, exposed through a Laravel API and used by a React/TypeScript screen. All four example totals from the brief are covered by tests.

![Basket with two red widgets totalling $54.37](docs/screenshot.png)

## Run

Requires Docker.

```sh
docker compose up --build
```

Open http://localhost:8080.

## Test

```sh
docker compose run --rm backend composer test    # PHPUnit: domain unit tests + API tests
docker compose run --rm backend composer lint    # Pint + PHPStan (level 8)
docker compose run --rm frontend npm test        # Vitest + Testing Library
docker compose run --rm frontend npm run lint    # Oxlint
```

The same checks run in [CI](.github/workflows/ci.yml).

## How it works

The basket lives in [`backend/app/Domain/Basket`](backend/app/Domain/Basket). It is plain PHP with no Laravel dependencies:

| Class | Role |
| --- | --- |
| `Basket` | Built with a catalogue, a delivery policy and a list of offers. Provides `add($code)`, `total()` and `breakdown()`. |
| `Money` | Integer cents, so the arithmetic is exact. |
| `Product`, `ProductCatalogue` | Products and lookup by code. |
| `Offer` → `HalfPriceSecondItemOffer` | Strategy interface: takes the basket lines and returns a discount. |
| `DeliveryPolicy` → `ThresholdDeliveryPolicy` | Strategy interface: takes the discounted subtotal and returns a delivery charge, using a list of `DeliveryBand`s. |
| `BasketLine`, `BasketTotals` | Immutable results of a calculation. |

```php
$basket = new Basket($catalogue, $deliveryPolicy, [new HalfPriceSecondItemOffer('R01')]);
$basket->add('R01');
$basket->add('R01');
$basket->total(); // "54.37"
```

Laravel only connects the model to HTTP:
- [`BasketServiceProvider`](backend/app/Providers/BasketServiceProvider.php) builds the model from [`config/basket.php`](backend/config/basket.php) and gives each request a new `Basket`.
- [`BasketController`](backend/app/Http/Controllers/BasketController.php) serves `GET /api/catalogue` and `POST /api/basket/quote`.

The React app in [`frontend/src`](frontend/src) stores only product codes and quantities. It asks the API for a fresh quote after every change, and [`useRequest`](frontend/src/hooks/useRequest.ts) cancels outdated requests so a slow response can't overwrite a newer one.

To add a new offer, implement `Offer` and register it in `BasketServiceProvider`. To change prices or delivery bands, edit `config/basket.php`.

## Assumptions

- **Delivery is charged on the subtotal after offers.** This is the only reading that matches the examples: two reds come to $65.90 − $16.48 = $49.42, which is under $50, so delivery is $4.95 and the total is $54.37.
- **The red-widget offer applies to every pair:** 4 reds include 2 half-price units.
- **Each half-price unit is rounded down to the cent:** $32.95 / 2 becomes $16.47. The examples don't decide this case. For four reds, rounding each half-price unit gives $98.84, while rounding the combined discount gives $98.85.
- An empty basket costs $0.00, with no delivery charge.
- Prices are in USD and include no tax. The API accepts each product once per request, with a quantity from 1 to 99.

## AI assistance

AI tools were used while building and documenting this project.
