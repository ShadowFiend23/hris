<?php

namespace App\Modules\Timekeeping;

use App\Modules\Timekeeping\Services\AttendanceService;
use App\Modules\Timekeeping\Services\DtrService;
use App\Modules\Timekeeping\Services\LeaveService;
use App\Modules\Timekeeping\Services\OvertimeService;
use App\Modules\Timekeeping\Services\ReportService;
use App\Modules\Timekeeping\Services\ShiftService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TimekeepingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Timekeeping services as singletons
        $this->app->singleton(AttendanceService::class);
        $this->app->singleton(LeaveService::class);
        $this->app->singleton(ShiftService::class);
        $this->app->singleton(OvertimeService::class);
        $this->app->singleton(ReportService::class);
        $this->app->singleton(DtrService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load Timekeeping web routes with web middleware
        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');

        // Load Timekeeping API routes with web middleware
        Route::middleware('web')
            ->group(__DIR__.'/routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
