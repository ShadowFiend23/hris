<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/timekeeping')->group(function () {
    // Timekeeping API routes
});
