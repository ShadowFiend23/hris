<?php

namespace App\Observers;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\LicenseService;

class CompanyObserver
{
    public function __construct(protected LicenseService $licenseService)
    {
    }

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        // Create a default license with all modules for the newly created company
        $this->licenseService->createDefaultLicense($company->id);
    }
}
