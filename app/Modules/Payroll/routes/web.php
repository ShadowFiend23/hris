<?php

use App\Modules\Payroll\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

// Payroll routes - require auth and payroll module access
Route::middleware(['auth', 'module.access:payroll'])->group(function () {
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll');
});
