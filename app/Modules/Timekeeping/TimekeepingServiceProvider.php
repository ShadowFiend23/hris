<?php

namespace App\Modules\Timekeeping;

use Illuminate\Support\ServiceProvider;

class TimekeepingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Timekeeping services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load Timekeeping routes
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
