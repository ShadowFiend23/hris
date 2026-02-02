<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class LicenseService
{
    public function getActiveModulesForCompany(int $companyId): Collection
    {
        return License::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->with('modules')
            ->get()
            ->flatMap(fn ($license) => $license->modules)
            ->unique('id');
    }

    /**
     * Get array of active module codes for a company
     *
     * @param int $companyId
     * @return array
     */
    public function getActiveModuleCodesForCompany(int $companyId): array
    {
        return $this->getActiveModulesForCompany($companyId)
            ->pluck('code')
            ->toArray();
    }

    /**
     * Create a default license with all active modules for a company
     *
     * @param int $companyId
     * @return License
     */
    public function createDefaultLicense(int $companyId): License
    {
        $license = License::create([
            'company_id' => $companyId,
            'license_key' => 'LIC-' . Str::random(12),
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now(),
            'valid_until' => now()->addYear(),
            'user_limit' => 999,
            'notes' => 'Default license created at company setup',
        ]);

        // Attach all active modules to the license
        $modules = Module::where('is_active', true)->pluck('id');
        $license->modules()->attach($modules);

        return $license;
    }

    /**
     * Get the active license for a company
     *
     * @param int $companyId
     * @return License|null
     */
    public function getCompanyActiveLicense(int $companyId): ?License
    {
        return License::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->first();
    }

    public function hasModuleAccess(int $companyId, string $moduleCode): bool
    {
        return $this->getActiveModulesForCompany($companyId)
            ->where('code', $moduleCode)
            ->isNotEmpty();
    }

    public function isLicenseValid(License $license): bool
    {
        return $license->status === 'active'
            && now()->between($license->valid_from, $license->valid_until);
    }

    public function isLicenseExpired(License $license): bool
    {
        return now()->isAfter($license->valid_until);
    }

    public function daysUntilExpiration(License $license): int
    {
        return now()->diffInDays($license->valid_until, false);
    }
}
