<?php

declare(strict_types=1);

namespace App\Domain\Basket;

use InvalidArgumentException;
use LogicException;

final readonly class ThresholdDeliveryPolicy implements DeliveryPolicy
{
    /** @var list<DeliveryBand> */
    private array $bands;

    // Bands are ordered by ascending limit; the first band that covers the amount sets the charge.
    public function __construct(DeliveryBand ...$bands)
    {
        $bands = array_values($bands);

        if ($bands === [] || $bands[array_key_last($bands)]->below !== null) {
            throw new InvalidArgumentException('The last delivery band must have no upper limit.');
        }

        $this->bands = $bands;
    }

    public function chargeFor(Money $discountedSubtotal): Money
    {
        foreach ($this->bands as $band) {
            if ($band->covers($discountedSubtotal)) {
                return $band->charge;
            }
        }

        throw new LogicException('Unreachable: the last band has no upper limit.');
    }
}
