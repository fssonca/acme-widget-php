<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;
use LogicException;

final readonly class ThresholdDeliveryPolicy implements DeliveryPolicy
{
    /** @param array<int, array{upperBoundCents: int|null, chargeCents: int}> $bands */
    public function __construct(private array $bands)
    {
        if ($bands === [] || ! array_is_list($bands)) {
            throw new InvalidArgumentException('Delivery bands must be a nonempty list.');
        }

        $previousBound = 0;
        $lastIndex = array_key_last($bands);

        foreach ($bands as $index => $band) {
            if ($band['chargeCents'] < 0) {
                throw new InvalidArgumentException('Delivery charges cannot be negative.');
            }

            $upperBound = $band['upperBoundCents'];

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

        if ($bands[$lastIndex]['upperBoundCents'] !== null) {
            throw new InvalidArgumentException('The last delivery band must be unbounded.');
        }
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
