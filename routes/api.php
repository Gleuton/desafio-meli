<?php

use App\Http\Controllers\Api\ItemsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->group(function () {
        Route::get('/items', [ItemsController::class, 'items']);
    });
