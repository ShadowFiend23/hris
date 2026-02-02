<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;
use App\Modules\Core\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseAccessTest extends TestCase
{
    use RefreshDatabase;

    protected LicenseService $licenseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->licenseService = app(LicenseService::class);
    }

    public function test_user_with_valid_license_can_access_module()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user
        $user = User::factory()->create(['company_id' => $company->id]);

        // Create modules
        $module = Module::factory()->create(['code' => 'hris']);

        // Create active license
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-001',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Attach module
        $license->modules()->attach($module->id);

        // Assert access
        $this->assertTrue(
            $this->licenseService->hasModuleAccess($company->id, 'hris')
        );
    }

    public function test_user_with_expired_license_cannot_access_module()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user
        $user = User::factory()->create(['company_id' => $company->id]);

        // Create module
        $module = Module::factory()->create(['code' => 'payroll']);

        // Create expired license
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-002',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subYear(),
            'valid_until' => now()->subDay(),
            'user_limit' => 100,
        ]);

        // Attach module
        $license->modules()->attach($module->id);

        // Assert no access
        $this->assertFalse(
            $this->licenseService->hasModuleAccess($company->id, 'payroll')
        );
    }

    public function test_user_with_inactive_license_cannot_access_module()
    {
        // Create company
        $company = Company::factory()->create();

        // Create module
        $module = Module::factory()->create(['code' => 'timekeeping']);

        // Create inactive license
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-003',
            'type' => 'enterprise',
            'status' => 'inactive',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Attach module
        $license->modules()->attach($module->id);

        // Assert no access
        $this->assertFalse(
            $this->licenseService->hasModuleAccess($company->id, 'timekeeping')
        );
    }

    public function test_middleware_blocks_access_to_locked_module()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user without module access
        $user = User::factory()->create(['company_id' => $company->id]);

        // Create inactive license without modules
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-004',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Act as user and try to access payroll
        $this->actingAs($user)
            ->get('/payroll')
            ->assertStatus(403);
    }

    public function test_user_can_access_multiple_modules()
    {
        // Create company
        $company = Company::factory()->create();

        // Create modules
        Module::factory()->create(['code' => 'hris']);
        Module::factory()->create(['code' => 'timekeeping']);
        Module::factory()->create(['code' => 'payroll']);

        // Create license with all modules
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-005',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Attach all modules
        $modules = Module::where('is_active', true)->pluck('id');
        $license->modules()->attach($modules);

        // Assert access to all modules
        $activeModuleCodes = $this->licenseService->getActiveModuleCodesForCompany($company->id);
        $this->assertContains('hris', $activeModuleCodes);
        $this->assertContains('timekeeping', $activeModuleCodes);
        $this->assertContains('payroll', $activeModuleCodes);
    }
}
