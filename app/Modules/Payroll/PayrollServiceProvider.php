<?php

namespace App\Modules\Payroll;

use Illuminate\Support\ServiceProvider;

class PayrollServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Payroll services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load Payroll routes
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
