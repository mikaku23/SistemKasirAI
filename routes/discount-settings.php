<?php

use App\Http\Controllers\DiscountSettingController;
use Illuminate\Support\Facades\Route;

Route::get('discount-settings/recycle', [DiscountSettingController::class, 'recycle'])->name('discount-settings.recycle');
Route::post('discount-settings/{discount_setting}/restore', [DiscountSettingController::class, 'restore'])->whereNumber('discount_setting')->name('discount-settings.restore');
Route::delete('discount-settings/{discount_setting}/force-delete', [DiscountSettingController::class, 'forceDelete'])->whereNumber('discount_setting')->name('discount-settings.forceDelete');
Route::resource('discount-settings', DiscountSettingController::class);
