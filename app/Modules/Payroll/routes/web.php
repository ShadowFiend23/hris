<?php

use App\Modules\Payroll\Controllers\AllowanceTypeController;
use App\Modules\Payroll\Controllers\EmployeeAllowanceController;
use App\Modules\Payroll\Controllers\EmployeePayslipController;
use App\Modules\Payroll\Controllers\HolidayController;
use App\Modules\Payroll\Controllers\LoanController;
use App\Modules\Payroll\Controllers\LoanTypeController;
use App\Modules\Payroll\Controllers\PayrollController;
use App\Modules\Payroll\Controllers\PayrollPeriodController;
use App\Modules\Payroll\Controllers\PayrollSettingsController;
use App\Modules\Payroll\Controllers\PayslipController;
use Illuminate\Support\Facades\Route;

// Payroll routes - require auth and payroll module access
Route::middleware(['auth', 'module.access:payroll'])->group(function (): void {
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll');

    // Payroll periods
    Route::get('/payroll/periods', [PayrollPeriodController::class, 'index'])->name('payroll.periods.index');
    Route::post('/payroll/periods', [PayrollPeriodController::class, 'store'])->name('payroll.periods.store');
    Route::get('/payroll/periods/{payrollPeriod}', [PayrollPeriodController::class, 'show'])->name('payroll.periods.show');
    Route::post('/payroll/periods/{payrollPeriod}/run', [PayrollPeriodController::class, 'run'])->name('payroll.periods.run');
    Route::post('/payroll/periods/{payrollPeriod}/finalize', [PayrollPeriodController::class, 'finalize'])->name('payroll.periods.finalize');

    // My Payslips (employee-facing)
    Route::get('/payroll/my-payslips', [EmployeePayslipController::class, 'index'])->name('payroll.my-payslips');
    Route::get('/api/payroll/my-payslips/{payrollItem}', [EmployeePayslipController::class, 'fetch'])->name('payroll.my-payslips.fetch');

    // Payslips
    Route::get('/payroll/payslips/{payrollItem}', [PayslipController::class, 'show'])->name('payroll.payslips.show');
    Route::get('/payroll/payslips/{payrollItem}/download', [PayslipController::class, 'download'])->name('payroll.payslips.download');

    // Settings (admin only)
    Route::get('/hr-settings/payroll', [PayrollSettingsController::class, 'index'])->name('payroll.settings.index');
    Route::post('/hr-settings/payroll', [PayrollSettingsController::class, 'store'])->name('payroll.settings.store');

    // Holidays (admin only) — lives under HR Settings
    Route::get('/hr-settings/holidays', [HolidayController::class, 'index'])->name('payroll.holidays.index');
    Route::post('/hr-settings/holidays', [HolidayController::class, 'store'])->name('payroll.holidays.store');
    Route::put('/hr-settings/holidays/{holiday}', [HolidayController::class, 'update'])->name('payroll.holidays.update');
    Route::delete('/hr-settings/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('payroll.holidays.destroy');

    // Loans
    Route::get('/loans', [LoanController::class, 'index'])->name('payroll.loans.index');
    Route::post('/loans', [LoanController::class, 'store'])->name('payroll.loans.store');
    Route::put('/loans/{loan}', [LoanController::class, 'update'])->name('payroll.loans.update');
    Route::delete('/loans/{loan}', [LoanController::class, 'destroy'])->name('payroll.loans.destroy');

    // Employee allowances (admin/manager only)
    Route::get('/api/employees/{employee}/allowances', [EmployeeAllowanceController::class, 'index'])->name('payroll.allowances.index');
    Route::post('/api/employees/{employee}/allowances', [EmployeeAllowanceController::class, 'store'])->name('payroll.allowances.store');
    Route::put('/api/employees/{employee}/allowances/{allowance}', [EmployeeAllowanceController::class, 'update'])->name('payroll.allowances.update');
    Route::delete('/api/employees/{employee}/allowances/{allowance}', [EmployeeAllowanceController::class, 'destroy'])->name('payroll.allowances.destroy');

    // HR Settings — Allowance Types + Loan Types (admin only)
    Route::prefix('hr-settings')->name('hr-settings.')->group(function (): void {
        Route::get('/allowance-types', [AllowanceTypeController::class, 'index'])->name('allowance-types.index');
        Route::post('/allowance-types', [AllowanceTypeController::class, 'store'])->name('allowance-types.store');
        Route::put('/allowance-types/{allowanceType}', [AllowanceTypeController::class, 'update'])->name('allowance-types.update');
        Route::delete('/allowance-types/{allowanceType}', [AllowanceTypeController::class, 'destroy'])->name('allowance-types.destroy');

        Route::get('/loan-types', [LoanTypeController::class, 'index'])->name('loan-types.index');
        Route::post('/loan-types', [LoanTypeController::class, 'store'])->name('loan-types.store');
        Route::put('/loan-types/{loanType}', [LoanTypeController::class, 'update'])->name('loan-types.update');
        Route::delete('/loan-types/{loanType}', [LoanTypeController::class, 'destroy'])->name('loan-types.destroy');
        Route::patch('/loan-settings/toggle', [LoanTypeController::class, 'toggleLoans'])->name('loan-settings.toggle');
    });

    // JSON API for type dropdowns
    Route::get('/api/hr-settings/allowance-types', [AllowanceTypeController::class, 'apiIndex'])->name('hr-settings.allowance-types.api');
    Route::get('/api/hr-settings/loan-types', [LoanTypeController::class, 'apiIndex'])->name('hr-settings.loan-types.api');
});
