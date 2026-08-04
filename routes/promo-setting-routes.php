<?php

use App\Http\Controllers\ProductPromoSettingController;
use Illuminate\Support\Facades\Route;

Route::resource('promo-settings', ProductPromoSettingController::class)->only([
    'index',
    'create',
    'store',
    'show',
    'edit',
    'update',
]);
