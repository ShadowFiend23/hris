<?php

namespace App\Modules\Payroll;

use App\Modules\Payroll\Services\HolidayPayService;
use App\Modules\Payroll\Services\NightDifferentialService;
use App\Modules\Payroll\Services\PagibigContributionService;
use App\Modules\Payroll\Services\PayrollCalculationService;
use App\Modules\Payroll\Services\PayrollPeriodService;
use App\Modules\Payroll\Services\PhilHealthContributionService;
use App\Modules\Payroll\Services\SSSContributionService;
use App\Modules\Payroll\Services\WithholdingTaxService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PayrollServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SSSContributionService::class);
        $this->app->singleton(PhilHealthContributionService::class);
        $this->app->singleton(PagibigContributionService::class);
        $this->app->singleton(WithholdingTaxService::class);
        $this->app->singleton(NightDifferentialService::class);
        $this->app->singleton(HolidayPayService::class);
        $this->app->singleton(PayrollPeriodService::class);
        $this->app->singleton(PayrollCalculationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load Payroll routes with web middleware
        Route::middleware('web')
            ->group(__DIR__.'/routes/web.php');
    }
}
