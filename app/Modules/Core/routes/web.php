<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Modules\Core\Controllers\DashboardController;
use App\Modules\Core\Controllers\EmployeesController;

// Core HRIS routes - all require auth and hris module access
Route::middleware(['auth', 'module.access:hris'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/employees', [EmployeesController::class, 'index'])->name('employees');
});
