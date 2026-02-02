<?php

namespace App\Modules\License;

use Illuminate\Support\ServiceProvider;

class LicenseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register License services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load License routes
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
