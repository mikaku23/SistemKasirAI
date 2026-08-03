<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
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
