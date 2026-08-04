<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('transactions/{transaction}/print', [TransactionController::class, 'print'])->whereNumber('transaction')->name('transactions.print');
Route::resource('transactions', TransactionController::class);
