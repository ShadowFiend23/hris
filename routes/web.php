<?php

use App\Http\Controllers\Auth\ForcePasswordController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// The Core module's routes at / take precedence for authenticated users
// This route is only hit for unauthenticated users
Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('welcome');

// Alias dashboard to root (Core module handles / for authenticated users)
Route::get('dashboard', function () {
    return redirect('/');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/change-password', [ForcePasswordController::class, 'show'])->name('password.change');
    Route::put('/change-password', [ForcePasswordController::class, 'update'])->name('password.change.update');
});

require __DIR__.'/settings.php';
