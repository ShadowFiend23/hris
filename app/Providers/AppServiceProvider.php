<?php

namespace App\Providers;

use App\Modules\Core\Models\Company;
use App\Observers\CompanyObserver;
use App\Services\ModuleLoaderService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(ModuleLoaderService::class)->loadModules();
        
        // Register observers
        Company::observe(CompanyObserver::class);
    }
}
