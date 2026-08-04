<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPromoSettingController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockBatchController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxSettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard-admin', function () {
    return view('admin.index', [
        'menu' => 'dashboard',
    ]);
})->name('dashboardadmin');

 Route::get('/', [AuthController::class, 'create'])->name('login.form');
    Route::post('/login', [AuthController::class, 'store'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Roles
Route::get('roles/recycle', [RoleController::class, 'recycle'])->name('roles.recycle');
Route::post('roles/{role}/restore', [RoleController::class, 'restore'])
    ->whereNumber('role')
    ->name('roles.restore');
Route::delete('roles/{role}/force-delete', [RoleController::class, 'forceDelete'])
    ->whereNumber('role')
    ->name('roles.forceDelete');
Route::resource('roles', RoleController::class);

// Users
Route::get('users/recycle', [UserController::class, 'recycle'])->name('users.recycle');
Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
Route::delete('users/{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.forceDelete');
Route::resource('users', UserController::class);

// Suppliers
Route::get('suppliers/recycle', [SupplierController::class, 'recycle'])->name('suppliers.recycle');
Route::post('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
Route::delete('suppliers/{supplier}/force-delete', [SupplierController::class, 'forceDelete'])->name('suppliers.forceDelete');
Route::resource('suppliers', SupplierController::class);

// units
Route::get('units/recycle', [UnitController::class, 'recycle'])->name('units.recycle');
Route::post('units/{unit}/restore', [UnitController::class, 'restore'])->name('units.restore');
Route::delete('units/{unit}/force-delete', [UnitController::class, 'forceDelete'])->name('units.forceDelete');
Route::resource('units', UnitController::class);

// products
Route::get('products/recycle', [ProductController::class, 'recycle'])->name('products.recycle');
Route::post('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
Route::delete('products/{product}/force-delete', [ProductController::class, 'forceDelete'])->name('products.forceDelete');
Route::resource('products', ProductController::class);

// categories
Route::get('categories/recycle', [CategoryController::class, 'recycle'])->name('categories.recycle');
Route::post('categories/{category}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
Route::delete('categories/{category}/force-delete', [CategoryController::class, 'forceDelete'])->name('categories.forceDelete');
Route::resource('categories', CategoryController::class);

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

Route::get('transactions/{transaction}/print', [TransactionController::class, 'print'])
    ->whereNumber('transaction')
    ->name('transactions.print');

Route::resource('transactions', TransactionController::class)->only([
    'index',
    'create',
    'store',
    'show',
]);

Route::get('locations/recycle', [LocationController::class, 'recycle'])->name('locations.recycle');
Route::post('locations/{location}/restore', [LocationController::class, 'restore'])
    ->whereNumber('location')
    ->name('locations.restore');
Route::delete('locations/{location}/force-delete', [LocationController::class, 'forceDelete'])
    ->whereNumber('location')
    ->name('locations.forceDelete');
Route::resource('locations', LocationController::class);

Route::get('tax-settings/recycle', [TaxSettingController::class, 'recycle'])->name('tax-settings.recycle');
Route::post('tax-settings/{tax_setting}/restore', [TaxSettingController::class, 'restore'])
    ->whereNumber('tax_setting')
    ->name('tax-settings.restore');
Route::delete('tax-settings/{tax_setting}/force-delete', [TaxSettingController::class, 'forceDelete'])
    ->whereNumber('tax_setting')
    ->name('tax-settings.forceDelete');
Route::resource('tax-settings', TaxSettingController::class);

Route::resource('promo-settings', ProductPromoSettingController::class)->only([
    'index',
    'create',
    'store',
    'show',
    'edit',
    'update',
]);

