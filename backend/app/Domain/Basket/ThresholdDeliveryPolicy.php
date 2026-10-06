<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;
use LogicException;

final readonly class ThresholdDeliveryPolicy implements DeliveryPolicy
{
    /** @var list<array{upperBoundCents: int|null, chargeCents: int}> */
    private array $bands;

    /** @param array<int, mixed> $bands Expected shape: list<array{upperBoundCents: int|null, chargeCents: int}>. */
    public function __construct(array $bands)
    {
        if ($bands === [] || ! array_is_list($bands)) {
            throw new InvalidArgumentException('Delivery bands must be a nonempty list.');
        }

        $previousBound = 0;
        $lastIndex = array_key_last($bands);
        $validatedBands = [];

        foreach ($bands as $index => $band) {
            if (! is_array($band)
                || ! array_key_exists('upperBoundCents', $band)
                || ! isset($band['chargeCents'])
                || ! is_int($band['chargeCents'])
                || ($band['upperBoundCents'] !== null && ! is_int($band['upperBoundCents']))) {
                throw new InvalidArgumentException('Delivery bands require an integer charge and an integer or null upper bound.');
            }

            if ($band['chargeCents'] < 0) {
                throw new InvalidArgumentException('Delivery charges cannot be negative.');
            }

            $upperBound = $band['upperBoundCents'];
            $validatedBands[] = ['upperBoundCents' => $upperBound, 'chargeCents' => $band['chargeCents']];

            if ($upperBound === null) {
                if ($index !== $lastIndex) {
                    throw new InvalidArgumentException('Only the last delivery band may be unbounded.');
                }

                continue;
            }

            if ($upperBound <= $previousBound) {
                throw new InvalidArgumentException('Delivery bounds must be positive and strictly increasing.');
            }

            $previousBound = $upperBound;
        }

        if ($validatedBands[$lastIndex]['upperBoundCents'] !== null) {
            throw new InvalidArgumentException('The last delivery band must be unbounded.');
        }

        $this->bands = $validatedBands;
    }

    public function chargeFor(Money $discountedSubtotal): Money
    {
        foreach ($this->bands as $band) {
            if ($band['upperBoundCents'] === null || $discountedSubtotal->cents < $band['upperBoundCents']) {
                return new Money($band['chargeCents']);
            }
        }

        throw new LogicException('Validated delivery bands must cover every nonnegative subtotal.');
    }
}
