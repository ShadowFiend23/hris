<?php

use App\Http\Controllers\DepartmentsController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\PositionsController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth:sanctum',config('jetstream.auth_session'),'verified',])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

Route::middleware(['auth:sanctum',config('jetstream.auth_session'),'verified',])->prefix('personnel')->group(function () {
    Route::get('/',fn() => redirect()->route('employees.index'));

    Route::apiResource('/employees',EmployeesController::class);

    Route::apiResource('/departments',DepartmentsController::class);

    Route::apiResource('/positions',PositionsController::class);
    
});
