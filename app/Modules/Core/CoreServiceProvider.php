<?php

namespace App\Modules\Core;

use App\Modules\Core\Services\LicenseService;
use App\Modules\Core\Services\ModuleService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register core services
        $this->app->singleton('core.license', LicenseService::class);
        $this->app->singleton('core.module', ModuleService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load core routes with web middleware
        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');

        // Load core API routes
        if (file_exists(__DIR__.'/routes/api.php')) {
            Route::middleware(['web', 'auth'])
                ->group(__DIR__.'/routes/api.php');
        }

        // Load migrations
        $this->loadMigrationsFrom(base_path('database/migrations'));
    }
}
