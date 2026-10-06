<?php

declare(strict_types=1);

return [
    'products' => [
        ['code' => 'R01', 'name' => 'Red Widget', 'unitPriceCents' => 3295],
        ['code' => 'G01', 'name' => 'Green Widget', 'unitPriceCents' => 2495],
        ['code' => 'B01', 'name' => 'Blue Widget', 'unitPriceCents' => 795],
    ],
    'delivery' => [
        ['upperBoundCents' => 5000, 'chargeCents' => 495],
        ['upperBoundCents' => 9000, 'chargeCents' => 295],
        ['upperBoundCents' => null, 'chargeCents' => 0],
    ],
    'offer' => [
        'targetCode' => 'R01',
        'description' => 'Buy one red widget, get the second half price. Every complete pair qualifies; each half-price widget costs $16.47.',
    ],
];
