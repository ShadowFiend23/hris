<?php

use App\Modules\Core\Controllers\DashboardController;
use App\Modules\Core\Controllers\DepartmentController;
use App\Modules\Core\Controllers\EmployeesController;
use App\Modules\Core\Controllers\EmployeeSettingsController;
use App\Modules\Core\Controllers\NotificationsController;
use App\Modules\Core\Controllers\PositionController;
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

    // HR Settings — Employee Settings (departments & positions)
    Route::prefix('hr-settings')->name('hr-settings.')->group(function () {
        Route::get('/employee-settings', [EmployeeSettingsController::class, 'index'])->name('employee-settings.index');

        // Departments
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::patch('/departments/{department}/toggle', [DepartmentController::class, 'toggle'])->name('departments.toggle');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        // Positions
        Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
        Route::put('/positions/{position}', [PositionController::class, 'update'])->name('positions.update');
        Route::patch('/positions/{position}/toggle', [PositionController::class, 'toggle'])->name('positions.toggle');
        Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
    });
});
