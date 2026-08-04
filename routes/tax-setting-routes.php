<?php

use App\Http\Controllers\TaxSettingController;
use Illuminate\Support\Facades\Route;

Route::get('tax-settings/recycle', [TaxSettingController::class, 'recycle'])->name('tax-settings.recycle');
Route::post('tax-settings/{tax_setting}/restore', [TaxSettingController::class, 'restore'])
    ->whereNumber('tax_setting')
    ->name('tax-settings.restore');
Route::delete('tax-settings/{tax_setting}/force-delete', [TaxSettingController::class, 'forceDelete'])
    ->whereNumber('tax_setting')
    ->name('tax-settings.forceDelete');
Route::resource('tax-settings', TaxSettingController::class);
