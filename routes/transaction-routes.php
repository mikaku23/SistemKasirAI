<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('transactions/{transaction}/print', [TransactionController::class, 'print'])->whereNumber('transaction')->name('transactions.print');
Route::get('transactions/barcode/{barcode}', [TransactionController::class, 'lookupBarcode'])->where('barcode', '[0-9]+')->name('transactions.barcode-lookup');
Route::resource('transactions', TransactionController::class);
