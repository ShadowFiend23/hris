<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/payroll')->group(function () {
    // Payroll API routes
});
