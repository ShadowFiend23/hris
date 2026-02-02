<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Core\Services\LicenseService;

class ModulePolicy
{
    public function __construct(protected LicenseService $licenseService)
    {
    }

    /**
     * Check if a user can access a specific module
     */
    public function access(User $user, string $moduleCode): bool
    {
        if (!$user->company_id) {
            return false;
        }

        return $this->licenseService->hasModuleAccess($user->company_id, $moduleCode);
    }
}
