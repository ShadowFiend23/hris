<?php

use App\Modules\License\Controllers\LicenseActivationController;
use App\Modules\License\Controllers\LicenseController;
use Illuminate\Support\Facades\Route;

// Hardware license activation — no auth required, runs before login
Route::middleware('web')->group(function () {
    Route::get('/license/activate', [LicenseActivationController::class, 'show'])->name('license.activate');
    Route::post('/license/activate', [LicenseActivationController::class, 'activate'])->name('license.activate.submit');
});

// DB-based license management — admin only
Route::middleware(['web', 'auth'])->prefix('license')->group(function () {
    Route::get('/', [LicenseController::class, 'index'])->name('license.index');
    Route::resource('licenses', LicenseController::class);

    Route::post('/licenses/{license}/modules', [LicenseController::class, 'attachModule'])->name('licenses.attach-module');
    Route::delete('/licenses/{license}/modules/{module}', [LicenseController::class, 'detachModule'])->name('licenses.detach-module');
});
