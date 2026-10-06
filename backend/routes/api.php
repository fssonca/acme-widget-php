<?php

declare(strict_types=1);

use App\Http\Controllers\BasketController;
use Illuminate\Support\Facades\Route;

Route::get('/catalogue', [BasketController::class, 'catalogue']);
Route::post('/basket/quote', [BasketController::class, 'quote']);
