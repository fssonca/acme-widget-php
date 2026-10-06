<?php

declare(strict_types=1);

// Catalogue, delivery tiers and offer used to build the basket; all amounts are in cents.
return [
    'products' => [
        ['code' => 'R01', 'name' => 'Red Widget', 'unitPriceCents' => 3295],
        ['code' => 'G01', 'name' => 'Green Widget', 'unitPriceCents' => 2495],
        ['code' => 'B01', 'name' => 'Blue Widget', 'unitPriceCents' => 795],
    ],
    'delivery' => [
        ['belowCents' => 5000, 'chargeCents' => 495],
        ['belowCents' => 9000, 'chargeCents' => 295],
        ['belowCents' => null, 'chargeCents' => 0],
    ],
    'offer' => [
        'productCode' => 'R01',
        'description' => 'Buy one red widget, get the second half price.',
    ],
];
