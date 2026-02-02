<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class ModuleLoaderService
{
    /**
     * Array of available modules with their service providers
     */
    protected array $modules = [
        'Core' => \App\Modules\Core\CoreServiceProvider::class,
        'HRIS' => \App\Modules\HRIS\HRISServiceProvider::class,
        'Timekeeping' => \App\Modules\Timekeeping\TimekeepingServiceProvider::class,
        'Payroll' => \App\Modules\Payroll\PayrollServiceProvider::class,
        'License' => \App\Modules\License\LicenseServiceProvider::class,
    ];

    /**
     * Load all modules
     */
    public function loadModules(): void
    {
        foreach ($this->modules as $providerClass) {
            if (class_exists($providerClass)) {
                app()->register($providerClass);
            }
        }
    }

    /**
     * Load routes for a specific module
     */
    public function loadModuleRoutes(string $moduleName): void
    {
        $modulePath = app_path("Modules/{$moduleName}");

        if (!File::exists($modulePath)) {
            return;
        }

        // Load web routes
        $webRoutes = $modulePath . '/routes/web.php';
        if (File::exists($webRoutes)) {
            Route::middleware(['web', 'auth'])->group($webRoutes);
        }

        // Load API routes
        $apiRoutes = $modulePath . '/routes/api.php';
        if (File::exists($apiRoutes)) {
            Route::middleware(['api', 'auth:sanctum'])->group($apiRoutes);
        }
    }

    /**
     * Get all registered modules
     */
    public function getModules(): array
    {
        return array_keys($this->modules);
    }

    /**
     * Check if a module is available
     */
    public function isModuleAvailable(string $moduleName): bool
    {
        return isset($this->modules[$moduleName]);
    }
}
