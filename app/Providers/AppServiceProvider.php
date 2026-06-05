<?php

namespace App\Providers;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\Holiday;
use App\Modules\Payroll\Models\Loan;
use App\Modules\Payroll\Models\PayrollItem;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Payroll\Models\PayrollSetting;
use App\Observers\CompanyObserver;
use App\Policies\EmployeePolicy;
use App\Policies\PayrollPolicy;
use App\Services\ModuleLoaderService;
use Illuminate\Support\Facades\Gate;
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

        // Register policies
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(PayrollPeriod::class, PayrollPolicy::class);
        Gate::policy(PayrollItem::class, PayrollPolicy::class);
        Gate::policy(PayrollSetting::class, PayrollPolicy::class);
        Gate::policy(Holiday::class, PayrollPolicy::class);
        Gate::policy(Loan::class, PayrollPolicy::class);
    }
}
