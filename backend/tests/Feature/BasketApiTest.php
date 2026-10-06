<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\QuoteBasket;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BasketApiTest extends TestCase
{
    public function test_catalogue_exposes_configured_products_and_offer(): void
    {
        $this->get('/api/catalogue')->assertOk()->assertExactJson([
            'currency' => 'USD',
            'products' => [
                ['code' => 'R01', 'name' => 'Red Widget', 'unitPriceCents' => 3295],
                ['code' => 'G01', 'name' => 'Green Widget', 'unitPriceCents' => 2495],
                ['code' => 'B01', 'name' => 'Blue Widget', 'unitPriceCents' => 795],
            ],
            'offerDescription' => Config::string('basket.offer.description'),
        ]);
    }

    /** @param list<array{code: string, quantity: int}> $items */
    #[DataProvider('acceptanceBaskets')]
    public function test_acceptance_totals_through_http(array $items, int $total): void
    {
        $response = $this->postJson('/api/basket/quote', ['items' => $items]);

        $response->assertOk()->assertJsonPath('currency', 'USD')->assertJsonPath('totalCents', $total);
        $this->assertSame($response->json('subtotalCents') - $response->json('discountCents'), $response->json('discountedSubtotalCents'));
        $this->assertSame($response->json('discountedSubtotalCents') + $response->json('deliveryCents'), $response->json('totalCents'));
    }

    /** @return array<string, array{list<array{code: string, quantity: int}>, int}> */
    public static function acceptanceBaskets(): array
    {
        return [
            'blue and green' => [[['code' => 'B01', 'quantity' => 1], ['code' => 'G01', 'quantity' => 1]], 3785],
            'two red' => [[['code' => 'R01', 'quantity' => 2]], 5437],
            'red and green' => [[['code' => 'R01', 'quantity' => 1], ['code' => 'G01', 'quantity' => 1]], 6085],
            'two blue and three red' => [[['code' => 'B01', 'quantity' => 2], ['code' => 'R01', 'quantity' => 3]], 9827],
        ];
    }

    public function test_two_red_breakdown_uses_gross_lines_and_net_delivery(): void
    {
        $this->postJson('/api/basket/quote', ['items' => [['code' => 'R01', 'quantity' => 2]]])
            ->assertOk()->assertExactJson([
                'currency' => 'USD',
                'items' => [['code' => 'R01', 'name' => 'Red Widget', 'quantity' => 2, 'unitPriceCents' => 3295, 'lineSubtotalCents' => 6590]],
                'subtotalCents' => 6590,
                'discountCents' => 1648,
                'discountedSubtotalCents' => 4942,
                'deliveryCents' => 495,
                'totalCents' => 5437,
            ]);
    }

    public function test_empty_list_has_all_zero_amounts(): void
    {
        $this->postJson('/api/basket/quote', ['items' => []])->assertOk()->assertExactJson([
            'currency' => 'USD', 'items' => [], 'subtotalCents' => 0, 'discountCents' => 0,
            'discountedSubtotalCents' => 0, 'deliveryCents' => 0, 'totalCents' => 0,
        ]);
    }

    /** @param array<string, mixed> $body */
    #[DataProvider('invalidFields')]
    public function test_invalid_fields_return_json_validation_errors(array $body, string $field): void
    {
        $this->postJson('/api/basket/quote', $body, options: JSON_PRESERVE_ZERO_FRACTION)->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidFields(): array
    {
        $cases = [
            'missing items' => [[], 'items'],
            'unknown code' => [['items' => [['code' => 'X01', 'quantity' => 1]]], 'items.0.code'],
            'case-sensitive code' => [['items' => [['code' => 'r01', 'quantity' => 1]]], 'items.0.code'],
            'numeric code' => [['items' => [['code' => 123, 'quantity' => 1]]], 'items.0.code'],
            'missing code' => [['items' => [['quantity' => 1]]], 'items.0.code'],
            'missing quantity' => [['items' => [['code' => 'R01']]], 'items.0.quantity'],
            'non-array item' => [['items' => ['R01']], 'items.0'],
            'sparse form list' => [['items' => [1 => ['code' => 'R01', 'quantity' => 1]]], 'items'],
            'row price' => [['items' => [['code' => 'R01', 'quantity' => 2, 'unitPriceCents' => 1]]], 'items.0'],
            'row discount' => [['items' => [['code' => 'R01', 'quantity' => 2, 'discountCents' => 6590]]], 'items.0'],
            'too many rows' => [['items' => array_fill(0, 1001, ['code' => 'B01', 'quantity' => 1])], 'items'],
            'too many units across duplicate rows' => [['items' => [['code' => 'R01', 'quantity' => 600], ['code' => 'R01', 'quantity' => 600]]], 'items'],
            'too many units across different codes' => [['items' => [['code' => 'R01', 'quantity' => 600], ['code' => 'B01', 'quantity' => 600]]], 'items'],
        ];

        foreach (['true' => true, 'false' => false, 'float' => 2.0, 'fraction' => 1.5, 'numeric string' => '2', 'null' => null, 'zero' => 0, 'negative' => -1, 'over limit' => 1001, 'array' => []] as $name => $quantity) {
            $cases[$name.' quantity'] = [['items' => [['code' => 'R01', 'quantity' => $quantity]]], 'items.0.quantity'];
        }

        return $cases;
    }

    #[DataProvider('invalidContainers')]
    public function test_wrong_json_containers_return_422_without_accept_header(string $body): void
    {
        $this->call('POST', '/api/basket/quote', server: ['CONTENT_TYPE' => 'application/json'], content: $body)
            ->assertUnprocessable()->assertHeader('Content-Type', 'application/json')->assertJsonValidationErrors('items');
    }

    /** @return array<string, array{string}> */
    public static function invalidContainers(): array
    {
        return [
            'root array' => ['[]'], 'root null' => ['null'], 'root string' => ['"items"'], 'root number' => ['1'],
            'root boolean' => ['true'], 'empty items object' => ['{"items":{}}'],
            'numeric-key items object' => ['{"items":{"0":{"code":"R01","quantity":1}}}'],
            'items null' => ['{"items":null}'], 'items string' => ['{"items":"R01"}'],
        ];
    }

    #[DataProvider('malformedBodies')]
    public function test_malformed_json_returns_400_without_accept_header(string $body): void
    {
        $this->call('POST', '/api/basket/quote', server: ['CONTENT_TYPE' => 'application/json'], content: $body)
            ->assertBadRequest()->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('message', 'Malformed JSON request body.');
    }

    /** @return array<string, array{string}> */
    public static function malformedBodies(): array
    {
        return ['truncated' => ['{"items":'], 'trailing comma' => ['{"items":[],}'], 'empty body' => [''], 'invalid UTF-8' => ["{\"items\":\"\xFF\"}"]];
    }

    public function test_plain_post_validation_is_json_without_redirect(): void
    {
        $this->post('/api/basket/quote', ['items' => [['code' => 'unknown', 'quantity' => 1]]])
            ->assertUnprocessable()->assertHeader('Content-Type', 'application/json')->assertJsonValidationErrors('items.0.code');
    }

    public function test_duplicate_rows_are_equivalent_to_merged_quantities(): void
    {
        $merged = $this->postJson('/api/basket/quote', ['items' => [['code' => 'R01', 'quantity' => 3], ['code' => 'B01', 'quantity' => 2]]])->assertOk()->json();

        $this->postJson('/api/basket/quote', ['items' => [
            ['code' => 'R01', 'quantity' => 1], ['code' => 'B01', 'quantity' => 2], ['code' => 'R01', 'quantity' => 2],
        ]])->assertOk()->assertExactJson($merged);
    }

    public function test_1000_units_succeed_in_one_or_many_rows(): void
    {
        $merged = $this->postJson('/api/basket/quote', ['items' => [['code' => 'B01', 'quantity' => 1000]]])
            ->assertOk()->assertJsonPath('totalCents', 795000)->assertJsonPath('items.0.quantity', 1000)->json();

        $this->postJson('/api/basket/quote', ['items' => array_fill(0, 1000, ['code' => 'B01', 'quantity' => 1])])
            ->assertOk()->assertExactJson($merged);
    }

    public function test_top_level_pricing_is_ignored(): void
    {
        $normal = $this->postJson('/api/basket/quote', ['items' => [['code' => 'R01', 'quantity' => 2]]])->assertOk()->json();

        $this->postJson('/api/basket/quote', [
            'items' => [['code' => 'R01', 'quantity' => 2]],
            'unitPriceCents' => 1, 'discountCents' => 6590, 'deliveryCents' => 0, 'totalCents' => 0,
        ])->assertOk()->assertExactJson($normal);
    }

    public function test_requests_do_not_share_basket_contents_even_with_a_shared_use_case(): void
    {
        $service = $this->app->make(QuoteBasket::class);
        $this->postJson('/api/basket/quote', ['items' => [['code' => 'R01', 'quantity' => 2]]])->assertOk()->assertJsonPath('totalCents', 5437);
        $this->postJson('/api/basket/quote', ['items' => [['code' => 'B01', 'quantity' => 1]]])->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('totalCents', 1290);
        $this->postJson('/api/basket/quote', ['items' => []])->assertOk()->assertJsonPath('items', [])->assertJsonPath('totalCents', 0);
        $this->assertSame($service, $this->app->make(QuoteBasket::class));
    }

    public function test_configured_catalogue_offer_and_delivery_are_used_in_validation_and_quotes(): void
    {
        Config::set('basket.products', [['code' => '123', 'name' => 'Test Widget', 'unitPriceCents' => 1001]]);
        Config::set('basket.offer.targetCode', '123');
        Config::set('basket.offer.description', 'An alternative offer.');
        Config::set('basket.delivery', [['upperBoundCents' => null, 'chargeCents' => 123]]);

        $this->getJson('/api/catalogue')->assertOk()->assertJsonPath('products.0.code', '123')->assertJsonPath('offerDescription', 'An alternative offer.');
        $this->postJson('/api/basket/quote', ['items' => [['code' => '123', 'quantity' => 2]]])
            ->assertOk()->assertJsonPath('items.0.code', '123')->assertJsonPath('discountCents', 501)->assertJsonPath('deliveryCents', 123)->assertJsonPath('totalCents', 1624);
        $this->postJson('/api/basket/quote', ['items' => [['code' => 'R01', 'quantity' => 1]]])->assertUnprocessable()->assertJsonValidationErrors('items.0.code');
    }
}
