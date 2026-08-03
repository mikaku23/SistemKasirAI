<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UnitController;

// Categories
Route::get('categories/recycle', [CategoryController::class, 'recycle'])->name('categories.recycle');
Route::post('categories/{category}/restore', [CategoryController::class, 'restore'])
    ->whereNumber('category')
    ->name('categories.restore');
Route::delete('categories/{category}/force-delete', [CategoryController::class, 'forceDelete'])
    ->whereNumber('category')
    ->name('categories.forceDelete');
Route::resource('categories', CategoryController::class);

// Units
Route::get('units/recycle', [UnitController::class, 'recycle'])->name('units.recycle');
Route::post('units/{unit}/restore', [UnitController::class, 'restore'])
    ->whereNumber('unit')
    ->name('units.restore');
Route::delete('units/{unit}/force-delete', [UnitController::class, 'forceDelete'])
    ->whereNumber('unit')
    ->name('units.forceDelete');
Route::resource('units', UnitController::class);

// Products
Route::get('products/recycle', [ProductController::class, 'recycle'])->name('products.recycle');
Route::post('products/{product}/restore', [ProductController::class, 'restore'])
    ->whereNumber('product')
    ->name('products.restore');
Route::delete('products/{product}/force-delete', [ProductController::class, 'forceDelete'])
    ->whereNumber('product')
    ->name('products.forceDelete');
Route::resource('products', ProductController::class);
