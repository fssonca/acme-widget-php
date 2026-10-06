<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Basket\ProductCatalogue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use JsonException;
use stdClass;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class QuoteBasketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->isJson()) {
            return;
        }

        try {
            $body = json_decode($this->getContent(), flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BadRequestHttpException('Malformed JSON request body.', $exception);
        }

        if (! $body instanceof stdClass) {
            throw ValidationException::withMessages(['items' => 'The request body must be a JSON object.']);
        }

        if (property_exists($body, 'items') && ! is_array($body->items)) {
            throw ValidationException::withMessages(['items' => 'The items field must be a JSON array.']);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(ProductCatalogue $catalogue): array
    {
        return [
            'items' => ['present', 'list', 'max:1000'],
            'items.*' => ['required', 'array:code,quantity'],
            'items.*.code' => ['bail', 'required', 'string', Rule::in($catalogue->codes())],
            'items.*.quantity' => ['bail', 'required', 'integer:strict', 'min:1', 'max:1000'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [static function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var list<array{code: string, quantity: int}> $items */
            $items = $validator->validated()['items'];

            if (array_sum(array_column($items, 'quantity')) > 1000) {
                $validator->errors()->add('items', 'A basket may contain at most 1000 total units.');
            }
        }];
    }

    /** @return list<array{code: string, quantity: int}> */
    public function items(): array
    {
        /** @var list<array{code: string, quantity: int}> $items */
        $items = $this->validated('items');

        return $items;
    }
}
