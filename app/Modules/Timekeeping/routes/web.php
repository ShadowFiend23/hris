<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Timekeeping\Controllers\TimekeepingController;

// Timekeeping routes - require auth and timekeeping module access
Route::middleware(['auth', 'module.access:timekeeping'])->group(function () {
    Route::get('/timekeeping', [TimekeepingController::class, 'index'])->name('timekeeping');
});
