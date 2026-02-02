<?php

use App\Modules\License\Controllers\LicenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('license')->group(function () {
    // License resourceful routes
    Route::resource('licenses', LicenseController::class);

    // Custom routes for module attachment/detachment
    Route::post('/licenses/{license}/modules', [LicenseController::class, 'attachModule'])->name('licenses.attach-module');
    Route::delete('/licenses/{license}/modules/{module}', [LicenseController::class, 'detachModule'])->name('licenses.detach-module');
});
