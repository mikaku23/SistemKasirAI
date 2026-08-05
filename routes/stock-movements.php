<?php

use App\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
Route::get('stock-movements/{stockMovement}', [StockMovementController::class, 'show'])
    ->whereNumber('stockMovement')
    ->name('stock-movements.show');
