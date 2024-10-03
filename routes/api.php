<?php

use App\Http\Controllers\EmployeesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PositionsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/employees/search',[EmployeesController::class, 'search'])->name('employees.search');
Route::get('/positions/search',[PositionsController::class, 'search'])->name('positions.search');
