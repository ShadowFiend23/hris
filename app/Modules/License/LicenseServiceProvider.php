<?php

namespace App\Modules\License;

use App\Modules\License\Services\HardwareFingerprint;
use App\Modules\License\Services\LicenseFile;
use Illuminate\Support\ServiceProvider;

class LicenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HardwareFingerprint::class);

        $this->app->singleton(LicenseFile::class, function ($app) {
            return new LicenseFile($app->make(HardwareFingerprint::class));
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
