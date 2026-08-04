<?php

use App\Http\Controllers\StockBatchController;
use Illuminate\Support\Facades\Route;

// Stock Batches / Barang Masuk
Route::get('stock-batches/recycle', [StockBatchController::class, 'recycle'])
    ->name('stock-batches.recycle');

Route::post('stock-batches/{stock_batch}/restore', [StockBatchController::class, 'restore'])
    ->whereNumber('stock_batch')
    ->name('stock-batches.restore');

Route::delete('stock-batches/{stock_batch}/force-delete', [StockBatchController::class, 'forceDelete'])
    ->whereNumber('stock_batch')
    ->name('stock-batches.forceDelete');

Route::resource('stock-batches', StockBatchController::class)
    ->parameters(['stock-batches' => 'stock_batch']);
