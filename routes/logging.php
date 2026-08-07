<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SystemLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');

    Route::get('/system-logs', [SystemLogController::class, 'index'])->name('system-logs.index');
    Route::get('/system-logs/{systemLog}', [SystemLogController::class, 'show'])->name('system-logs.show');
});
