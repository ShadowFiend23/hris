<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_license()
    {
        // Create company and admin user
        $company = Company::factory()->create();
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($adminRole);

        // Make request
        $response = $this->actingAs($user)->postJson('/license/licenses', [
            'license_key' => 'NEW-KEY-001',
            'type' => 'enterprise',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'user_limit' => 50,
            'status' => 'active',
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('licenses', [
            'license_key' => 'NEW-KEY-001',
            'company_id' => $company->id,
        ]);
    }

    public function test_non_admin_cannot_create_license()
    {
        // Create company and employee user
        $company = Company::factory()->create();
        $employeeRole = Role::create(['name' => 'Employee', 'slug' => 'employee']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($employeeRole);

        // Make request
        $response = $this->actingAs($user)->postJson('/license/licenses', [
            'license_key' => 'NEW-KEY-002',
            'type' => 'enterprise',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'user_limit' => 50,
            'status' => 'active',
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_admin_can_attach_module_to_license()
    {
        // Create setup
        $company = Company::factory()->create();
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($adminRole);

        $module = Module::factory()->create(['code' => 'hris']);

        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-001',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Make request
        $response = $this->actingAs($user)->postJson("/license/licenses/{$license->id}/modules", [
            'module_id' => $module->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertTrue($license->modules()->where('module_id', $module->id)->exists());
    }

    public function test_admin_can_detach_module_from_license()
    {
        // Create setup
        $company = Company::factory()->create();
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($adminRole);

        $module = Module::factory()->create(['code' => 'payroll']);

        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'TEST-KEY-002',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        $license->modules()->attach($module->id);

        // Make request
        $response = $this->actingAs($user)->deleteJson("/license/licenses/{$license->id}/modules/{$module->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertFalse($license->modules()->where('module_id', $module->id)->exists());
    }

    public function test_user_cannot_access_other_company_license()
    {
        // Create two companies
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Create users
        $user1 = User::factory()->create(['company_id' => $company1->id]);
        $user2 = User::factory()->create(['company_id' => $company2->id]);

        // Create license for company1
        $license = License::create([
            'company_id' => $company1->id,
            'license_key' => 'TEST-KEY-003',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addYear(),
            'user_limit' => 100,
        ]);

        // Try to access as user2
        $response = $this->actingAs($user2)->getJson("/license/licenses/{$license->id}");

        // Assert
        $response->assertStatus(403);
    }
}
