<?php

use App\Modules\Timekeeping\Controllers\CalendarController;
use App\Modules\Timekeeping\Controllers\DtrController;
use App\Modules\Timekeeping\Controllers\LeaveTypeController;
use App\Modules\Timekeeping\Controllers\ScheduleChangeController;
use App\Modules\Timekeeping\Controllers\ShiftTemplatesController;
use App\Modules\Timekeeping\Controllers\TimekeepingController;
use App\Modules\Timekeeping\Controllers\TimekeepingSettingsController;
use Illuminate\Support\Facades\Route;

// Timekeeping routes - require auth and timekeeping module access
Route::middleware(['auth', 'module.access:timekeeping'])->group(function () {
    Route::get('/timekeeping', [TimekeepingController::class, 'index'])->name('timekeeping');

    // DTR routes
    Route::get('/timekeeping/dtr', [DtrController::class, 'index'])->name('timekeeping.dtr');
    Route::get('/timekeeping/dtr/{employee}/download', [DtrController::class, 'download'])->name('timekeeping.dtr.download');
    Route::get('/api/timekeeping/dtr/data', [DtrController::class, 'data'])->name('timekeeping.dtr.data');

    // Dashboard calendar events API
    Route::get('/api/dashboard/calendar', [CalendarController::class, 'index'])->name('dashboard.calendar');

    // App Settings — admin-only leave type & shift template management
    Route::prefix('app-settings')->name('app-settings.')->group(function () {
        // Timekeeping Settings (Leave + OT toggles, approval chains, leave types)
        Route::get('/timekeeping-settings', [TimekeepingSettingsController::class, 'index'])->name('timekeeping.index');
        Route::patch('/timekeeping/toggle-leave', [TimekeepingSettingsController::class, 'toggleLeave'])->name('timekeeping.toggle-leave');
        Route::patch('/timekeeping/toggle-ot', [TimekeepingSettingsController::class, 'toggleOt'])->name('timekeeping.toggle-ot');
        Route::patch('/timekeeping/toggle-swap', [TimekeepingSettingsController::class, 'toggleSwapGlobal'])->name('timekeeping.toggle-swap');
        Route::post('/timekeeping/approval-chain', [TimekeepingSettingsController::class, 'saveApprovalChain'])->name('timekeeping.approval-chain');

        // Leave type CRUD (store/update/destroy stay on LeaveTypeController)
        Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
        Route::put('/leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->name('leave-types.update');
        Route::delete('/leave-types/{leaveType}', [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');

        Route::get('/shift-templates', [ShiftTemplatesController::class, 'index'])->name('shift-templates.index');
        Route::post('/shift-templates', [ShiftTemplatesController::class, 'store'])->name('shift-templates.store');
        Route::put('/shift-templates/{shiftTemplate}', [ShiftTemplatesController::class, 'update'])->name('shift-templates.update');
        Route::delete('/shift-templates/{shiftTemplate}', [ShiftTemplatesController::class, 'destroy'])->name('shift-templates.destroy');
        Route::patch('/shift-templates/{shiftTemplate}/toggle-swap', [ShiftTemplatesController::class, 'toggleSwap'])->name('shift-templates.toggle-swap');
    });

    // Schedule change request API routes
    Route::prefix('api/timekeeping/schedule-change')->name('timekeeping.schedule-change.')->group(function () {
        Route::get('/templates', [ScheduleChangeController::class, 'availableTemplates'])->name('templates');
        Route::get('/', [ScheduleChangeController::class, 'index'])->name('index');
        Route::post('/', [ScheduleChangeController::class, 'store'])->name('store');
        Route::post('/{scheduleChangeRequest}/cancel', [ScheduleChangeController::class, 'cancel'])->name('cancel');
        Route::get('/pending', [ScheduleChangeController::class, 'pending'])->name('pending');
        Route::post('/{scheduleChangeRequest}/approve', [ScheduleChangeController::class, 'approve'])->name('approve');
        Route::post('/{scheduleChangeRequest}/reject', [ScheduleChangeController::class, 'reject'])->name('reject');
    });
});
