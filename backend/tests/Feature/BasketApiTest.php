<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Basket\Money;
use App\Domain\Basket\Offer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BasketApiTest extends TestCase
{
    public function test_lists_the_configured_catalogue(): void
    {
        $this->getJson('/api/catalogue')
            ->assertOk()
            ->assertJsonPath('products.0', ['code' => 'R01', 'name' => 'Red Widget', 'unitPriceCents' => 3295])
            ->assertJsonCount(3, 'products')
            ->assertJsonPath('offers', ['Buy one red widget, get the second half price.']);
    }

    public function test_a_newly_registered_offer_is_listed_and_applied(): void
    {
        $this->app->instance('test.offer', new class implements Offer
        {
            public function discountFor(array $lines): Money
            {
                return new Money(100);
            }

            public function description(): string
            {
                return '$1 off every order';
            }
        });
        $this->app->tag(['test.offer'], 'basket.offers');

        $this->getJson('/api/catalogue')->assertJsonPath('offers.1', '$1 off every order');
        $this->postJson('/api/basket/quote', ['items' => [['code' => 'R01', 'quantity' => 2]]])
            ->assertJsonPath('discountCents', 1748)
            ->assertJsonPath('totalCents', 5337);
    }

    public function test_quotes_a_basket_with_a_full_breakdown(): void
    {
        $this->postJson('/api/basket/quote', ['items' => [['code' => 'R01', 'quantity' => 2]]])
            ->assertOk()
            ->assertExactJson([
                'items' => [['code' => 'R01', 'quantity' => 2, 'lineSubtotalCents' => 6590]],
                'subtotalCents' => 6590,
                'discountCents' => 1648,
                'deliveryCents' => 495,
                'totalCents' => 5437,
            ]);
    }

    public function test_quotes_the_last_challenge_example(): void
    {
        $this->postJson('/api/basket/quote', ['items' => [['code' => 'B01', 'quantity' => 2], ['code' => 'R01', 'quantity' => 3]]])
            ->assertOk()
            ->assertJsonPath('totalCents', 9827);
    }

    public function test_quotes_an_empty_basket(): void
    {
        $this->postJson('/api/basket/quote', ['items' => []])->assertOk()->assertJsonPath('totalCents', 0);
    }

    public function test_each_request_starts_with_an_empty_basket(): void
    {
        $this->postJson('/api/basket/quote', ['items' => [['code' => 'R01', 'quantity' => 2]]]);

        $this->postJson('/api/basket/quote', ['items' => [['code' => 'B01', 'quantity' => 1]]])
            ->assertJsonPath('totalCents', 1290);
    }

    /** @param array<string, mixed> $body */
    #[DataProvider('invalidBodies')]
    public function test_rejects_invalid_baskets(array $body, string $field): void
    {
        $this->postJson('/api/basket/quote', $body)->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidBodies(): array
    {
        return [
            'missing items' => [[], 'items'],
            'unknown code' => [['items' => [['code' => 'X01', 'quantity' => 1]]], 'items.0.code'],
            'repeated code' => [['items' => [['code' => 'R01', 'quantity' => 1], ['code' => 'R01', 'quantity' => 1]]], 'items.1.code'],
            'zero quantity' => [['items' => [['code' => 'R01', 'quantity' => 0]]], 'items.0.quantity'],
            'quantity over 99' => [['items' => [['code' => 'R01', 'quantity' => 100]]], 'items.0.quantity'],
            'string quantity' => [['items' => [['code' => 'R01', 'quantity' => '2']]], 'items.0.quantity'],
        ];
    }

    public function test_errors_are_json_without_an_accept_header(): void
    {
        $this->post('/api/basket/quote', ['items' => [['code' => 'X01', 'quantity' => 1]]])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json');
    }
}
