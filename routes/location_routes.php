<?php

use App\Http\Controllers\LocationController;

// Locations
Route::get('locations/recycle', [LocationController::class, 'recycle'])->name('locations.recycle');
Route::post('locations/{location}/restore', [LocationController::class, 'restore'])
    ->whereNumber('location')
    ->name('locations.restore');
Route::delete('locations/{location}/force-delete', [LocationController::class, 'forceDelete'])
    ->whereNumber('location')
    ->name('locations.forceDelete');
Route::resource('locations', LocationController::class);
