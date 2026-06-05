<?php

namespace Tests\Traits;

use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;

trait WithModuleAccess
{
    /**
     * Setup module access for a company
     */
    protected function setupModuleAccess(int $companyId, array $moduleCodes = ['hris']): License
    {
        // Create modules if they don't exist
        foreach ($moduleCodes as $code) {
            Module::firstOrCreate(
                ['code' => $code],
                [
                    'name' => ucfirst($code),
                    'description' => ucfirst($code).' module',
                    'is_active' => true,
                    'order' => 1,
                ]
            );
        }

        // Create an active license for the company
        $license = License::create([
            'company_id' => $companyId,
            'license_key' => 'TEST-'.uniqid(),
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 999,
            'notes' => 'Test license',
        ]);

        // Attach modules to license
        $moduleIds = Module::whereIn('code', $moduleCodes)->pluck('id');
        $license->modules()->attach($moduleIds);

        return $license;
    }
}
