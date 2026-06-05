<?php

use App\Modules\Timekeeping\Controllers\AttendanceAdjustController;
use App\Modules\Timekeeping\Controllers\AttendanceController;
use App\Modules\Timekeeping\Controllers\LeaveController;
use App\Modules\Timekeeping\Controllers\OvertimeController;
use App\Modules\Timekeeping\Controllers\ReportController;
use App\Modules\Timekeeping\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Timekeeping API Routes
|--------------------------------------------------------------------------
|
| Routes for the Timekeeping module. All routes require authentication
| and module access middleware.
|
*/

Route::middleware(['auth', 'module.access:timekeeping'])->prefix('api/timekeeping')->name('timekeeping.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Attendance Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('attendance')->name('attendance.')->group(function () {
        // Employee routes
        Route::get('/today', [AttendanceController::class, 'today'])->name('today');
        Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clock-out');
        Route::post('/break', [AttendanceController::class, 'recordBreak'])->name('break');
        Route::get('/history', [AttendanceController::class, 'history'])->name('history');
        Route::get('/summary', [AttendanceController::class, 'summary'])->name('summary');

        // Manager routes
        Route::get('/team', [AttendanceAdjustController::class, 'team'])->name('team');
        Route::patch('/{record}/adjust', [AttendanceAdjustController::class, 'adjust'])->name('adjust');

        // Admin routes
        Route::get('/company', [AttendanceController::class, 'companyToday'])->name('company');
        Route::get('/employee/{employee}', [AttendanceController::class, 'employeeAttendance'])->name('employee');
        Route::post('/employee/{employee}/absent', [AttendanceController::class, 'markAbsent'])->name('mark-absent');
        Route::put('/{record}', [AttendanceController::class, 'update'])->name('update');
    });

    /*
    |--------------------------------------------------------------------------
    | Leave Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('leave')->name('leave.')->group(function () {
        // Employee routes
        Route::get('/types', [LeaveController::class, 'types'])->name('types');
        Route::get('/balance', [LeaveController::class, 'balance'])->name('balance');
        Route::get('/history', [LeaveController::class, 'history'])->name('history');
        Route::post('/request', [LeaveController::class, 'store'])->name('store');
        Route::delete('/request/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('cancel');

        // Admin routes
        Route::get('/pending', [LeaveController::class, 'pending'])->name('pending');
        Route::post('/request/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('approve');
        Route::post('/request/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('reject');
        Route::get('/employee/{employee}/balance', [LeaveController::class, 'employeeBalance'])->name('employee-balance');
        Route::post('/employee/{employee}/initialize', [LeaveController::class, 'initializeBalance'])->name('initialize');
    });

    /*
    |--------------------------------------------------------------------------
    | Shift Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('shift')->name('shift.')->group(function () {
        // Template routes
        Route::get('/templates', [ShiftController::class, 'templates'])->name('templates');
        Route::post('/templates', [ShiftController::class, 'storeTemplate'])->name('templates.store');

        // Employee schedule routes
        Route::get('/my-schedule', [ShiftController::class, 'mySchedule'])->name('my-schedule');
        Route::get('/weekly', [ShiftController::class, 'weekly'])->name('weekly');

        // Shift swap routes
        Route::post('/swap/request', [ShiftController::class, 'requestSwap'])->name('swap.request');
        Route::get('/swap/pending', [ShiftController::class, 'pendingSwaps'])->name('swap.pending');
        Route::post('/swap/{swapRequest}/approve', [ShiftController::class, 'approveSwap'])->name('swap.approve');
        Route::post('/swap/{swapRequest}/reject', [ShiftController::class, 'rejectSwap'])->name('swap.reject');

        // Admin routes
        Route::post('/assign', [ShiftController::class, 'assign'])->name('assign');
        Route::post('/bulk-assign', [ShiftController::class, 'bulkAssign'])->name('bulk-assign');
        Route::get('/employee/{employee}', [ShiftController::class, 'employeeSchedule'])->name('employee-schedule');
    });

    /*
    |--------------------------------------------------------------------------
    | Overtime Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('overtime')->name('overtime.')->group(function () {
        // Employee routes
        Route::post('/request', [OvertimeController::class, 'store'])->name('store');
        Route::get('/history', [OvertimeController::class, 'history'])->name('history');
        Route::get('/summary', [OvertimeController::class, 'summary'])->name('summary');
        Route::delete('/{overtime}/cancel', [OvertimeController::class, 'cancel'])->name('cancel');

        // Admin routes
        Route::get('/pending', [OvertimeController::class, 'pending'])->name('pending');
        Route::post('/{overtime}/approve', [OvertimeController::class, 'approve'])->name('approve');
        Route::post('/{overtime}/reject', [OvertimeController::class, 'reject'])->name('reject');
        Route::post('/{overtime}/mark-paid', [OvertimeController::class, 'markPaid'])->name('mark-paid');
        Route::get('/employee/{employee}', [OvertimeController::class, 'employeeOvertime'])->name('employee');
        Route::get('/employee/{employee}/summary', [OvertimeController::class, 'employeeSummary'])->name('employee-summary');
    });

    /*
    |--------------------------------------------------------------------------
    | Report Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
        Route::get('/leave', [ReportController::class, 'leave'])->name('leave');
        Route::get('/overtime', [ReportController::class, 'overtime'])->name('overtime');
        Route::get('/employee/{employee}/summary', [ReportController::class, 'employeeSummary'])->name('employee-summary');
        Route::get('/my-summary', [ReportController::class, 'mySummary'])->name('my-summary');
    });
});
