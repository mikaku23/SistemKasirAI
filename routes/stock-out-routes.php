<?php

use App\Http\Controllers\StockOutController;
use Illuminate\Support\Facades\Route;

Route::get('stock-outs/recycle', [StockOutController::class, 'recycle'])->name('stock-outs.recycle');
Route::post('stock-outs/{sale}/restore', [StockOutController::class, 'restore'])
    ->whereNumber('sale')
    ->name('stock-outs.restore');
Route::delete('stock-outs/{sale}/force-delete', [StockOutController::class, 'forceDelete'])
    ->whereNumber('sale')
    ->name('stock-outs.forceDelete');
Route::resource('stock-outs', StockOutController::class)->except(['edit', 'update']);
