<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_protected_route()
    {
        $response = $this->get('/payroll');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_without_company_cannot_access_module()
    {
        $user = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($user)->get('/payroll');
        $response->assertStatus(403);
    }

    public function test_user_with_valid_license_can_access_payroll()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user
        $user = User::factory()->create(['company_id' => $company->id]);

        // Create payroll module
        $module = Module::factory()->create(['code' => 'payroll']);

        // Create active license
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-PAYROLL',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Attach payroll module
        $license->modules()->attach($module->id);

        // Access should be granted
        $response = $this->actingAs($user)->get('/payroll');
        $response->assertStatus(200);
    }

    public function test_user_without_payroll_license_cannot_access_payroll()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user
        $user = User::factory()->create(['company_id' => $company->id]);

        // Create payroll module (but don't attach to license)
        Module::factory()->create(['code' => 'payroll']);

        // Create active license without payroll module
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-NO-PAYROLL',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Access should be blocked
        $response = $this->actingAs($user)->get('/payroll');
        $response->assertStatus(403);
    }

    public function test_user_can_access_timekeeping_with_license()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user
        $user = User::factory()->create(['company_id' => $company->id]);

        // Create timekeeping module
        $module = Module::factory()->create(['code' => 'timekeeping']);

        // Create active license
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-TIMEKEEPING',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Attach timekeeping module
        $license->modules()->attach($module->id);

        // Access should be granted
        $response = $this->actingAs($user)->get('/timekeeping');
        $response->assertStatus(200);
    }

    public function test_user_with_expired_license_cannot_access_module()
    {
        // Create company
        $company = Company::factory()->create();

        // Create user
        $user = User::factory()->create(['company_id' => $company->id]);

        // Create hris module
        $module = Module::factory()->create(['code' => 'hris']);

        // Create expired license
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-EXPIRED',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subYear(),
            'valid_until' => now()->subDay(),
            'user_limit' => 100,
        ]);

        // Attach module
        $license->modules()->attach($module->id);

        // Access should be blocked
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(403);
    }

    public function test_multiple_users_with_different_licenses()
    {
        // Create two companies
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Create users
        $user1 = User::factory()->create(['company_id' => $company1->id]);
        $user2 = User::factory()->create(['company_id' => $company2->id]);

        // Create modules
        $payroll = Module::factory()->create(['code' => 'payroll']);
        $timekeeping = Module::factory()->create(['code' => 'timekeeping']);

        // Create licenses
        $license1 = License::create([
            'company_id' => $company1->id,
            'license_key' => 'LIC1',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        $license2 = License::create([
            'company_id' => $company2->id,
            'license_key' => 'LIC2',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Attach different modules
        $license1->modules()->attach($payroll->id);
        $license2->modules()->attach($timekeeping->id);

        // Test user1 can access payroll but not timekeeping
        $this->actingAs($user1)->get('/payroll')->assertStatus(200);
        $this->actingAs($user1)->get('/timekeeping')->assertStatus(403);

        // Test user2 can access timekeeping but not payroll
        $this->actingAs($user2)->get('/timekeeping')->assertStatus(200);
        $this->actingAs($user2)->get('/payroll')->assertStatus(403);
    }
}
