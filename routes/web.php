<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DiscountSettingController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LogTcController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPromoSettingController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockBatchController;
use App\Http\Controllers\StockMovementController;
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
Route::get('products/{product}/print-barcode', [ProductController::class, 'printBarcode'])
    ->whereNumber('product')
    ->name('products.print-barcode');
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

Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
Route::get('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'show'])
    ->whereNumber('stock_adjustment')
    ->name('stock-adjustments.show');
Route::post('stock-adjustments/{stock_adjustment}/confirm-system', [StockAdjustmentController::class, 'confirmSystemCorrect'])
    ->whereNumber('stock_adjustment')
    ->name('stock-adjustments.confirm-system');
Route::post('stock-adjustments/{stock_adjustment}/apply-correction', [StockAdjustmentController::class, 'applyCorrection'])
    ->whereNumber('stock_adjustment')
    ->name('stock-adjustments.apply-correction');

Route::get('transactions/{transaction}/print', [TransactionController::class, 'print'])->whereNumber('transaction')->name('transactions.print');
Route::get('transactions/barcode/{barcode}', [TransactionController::class, 'lookupBarcode'])->where('barcode', '[0-9]+')->name('transactions.barcode-lookup');
Route::resource('transactions', TransactionController::class);

Route::get('log-tc', [LogTcController::class, 'index'])->name('log-tc.index');
Route::get('log-tc/{transaction}', [LogTcController::class, 'show'])
    ->whereNumber('transaction')
    ->name('log-tc.show');

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

Route::resource('promo-settings', ProductPromoSettingController::class)
    ->parameters(['promo-settings' => 'product'])
    ->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
        'destroy',
    ]);

Route::get('discount-settings/recycle', [DiscountSettingController::class, 'recycle'])->name('discount-settings.recycle');
Route::post('discount-settings/{discount_setting}/restore', [DiscountSettingController::class, 'restore'])->whereNumber('discount_setting')->name('discount-settings.restore');
Route::delete('discount-settings/{discount_setting}/force-delete', [DiscountSettingController::class, 'forceDelete'])->whereNumber('discount_setting')->name('discount-settings.forceDelete');
Route::resource('discount-settings', DiscountSettingController::class);

Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
Route::get('stock-movements/{stockMovement}', [StockMovementController::class, 'show'])
    ->whereNumber('stockMovement')
    ->name('stock-movements.show');
