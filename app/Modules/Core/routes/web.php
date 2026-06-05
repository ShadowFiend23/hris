<?php

use App\Modules\Core\Controllers\DashboardController;
use App\Modules\Core\Controllers\EmployeesController;
use App\Modules\Core\Controllers\NotificationsController;
use Illuminate\Support\Facades\Route;

// Core HRIS routes - all require auth and hris module access
Route::middleware(['auth', 'module.access:hris'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');

    // Notifications (bell) — all roles
    Route::get('/api/core/notifications', [NotificationsController::class, 'index'])->name('notifications.index');

    // Employee resource routes
    Route::resource('employees', EmployeesController::class)->names([
        'index' => 'employees.index',
        'create' => 'employees.create',
        'store' => 'employees.store',
        'show' => 'employees.show',
        'edit' => 'employees.edit',
        'update' => 'employees.update',
        'destroy' => 'employees.destroy',
    ]);

    // Employee restore route (for soft-deleted employees)
    Route::post('employees/{employee}/restore', [EmployeesController::class, 'restore'])
        ->name('employees.restore')
        ->withTrashed();
});
