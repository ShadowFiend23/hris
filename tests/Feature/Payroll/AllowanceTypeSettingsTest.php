<?php

namespace Tests\Feature\Payroll;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Payroll\Models\AllowanceType;
use App\Modules\Payroll\Models\EmployeeAllowance;
use App\Modules\Payroll\Models\PayrollSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class AllowanceTypeSettingsTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id, ['payroll']);

        PayrollSetting::create([
            'company_id' => $this->company->id,
            'period_type' => 'semi_monthly',
            'pay_day_1' => 15,
            'pay_day_2' => 30,
            'work_days_per_month' => 26,
            'is_active' => true,
        ]);

        $this->adminUser = $this->userWithPermission('payroll.settings');
    }

    private function userWithPermission(string $permSlug): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $role = Role::create(['name' => 'Test '.uniqid(), 'slug' => 'test-'.uniqid()]);

        $permission = Permission::firstOrCreate(
            ['slug' => $permSlug],
            ['name' => $permSlug, 'slug' => $permSlug, 'group' => 'payroll']
        );
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        Employee::factory()->create([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
        ]);

        return $user->fresh('roles.permissions', 'employee');
    }

    public function test_index_shows_only_own_company_allowance_types(): void
    {
        $otherCompany = Company::factory()->create();
        $own = AllowanceType::create(['company_id' => $this->company->id, 'name' => 'Rice', 'code' => 'rice', 'is_active' => true, 'is_taxable' => false]);
        AllowanceType::create(['company_id' => $otherCompany->id, 'name' => 'Other', 'code' => 'other', 'is_active' => true, 'is_taxable' => false]);

        $this->actingAs($this->adminUser)
            ->get('/app-settings/allowance-types')
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRSettings/AllowanceTypes')
                ->has('allowanceTypes', 1)
                ->where('allowanceTypes.0.id', $own->id)
            );
    }

    public function test_store_creates_allowance_type(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/app-settings/allowance-types', [
                'name' => 'Gas Allowance',
                'code' => 'gas',
                'default_amount' => 1500,
                'is_taxable' => false,
            ])
            ->assertRedirect('/app-settings/allowance-types');

        $this->assertDatabaseHas('allowance_types', [
            'company_id' => $this->company->id,
            'name' => 'Gas Allowance',
            'code' => 'gas',
            'is_active' => true,
        ]);
    }

    public function test_update_allowance_type(): void
    {
        $type = AllowanceType::create([
            'company_id' => $this->company->id,
            'name' => 'Rice',
            'code' => 'rice',
            'is_active' => true,
            'is_taxable' => false,
        ]);

        $this->actingAs($this->adminUser)
            ->put("/app-settings/allowance-types/{$type->id}", [
                'name' => 'Rice Allowance',
                'default_amount' => 2000,
                'is_taxable' => 0,
                'is_active' => 1,
            ])
            ->assertRedirect('/app-settings/allowance-types');

        $this->assertDatabaseHas('allowance_types', [
            'id' => $type->id,
            'name' => 'Rice Allowance',
            'default_amount' => 2000,
        ]);
    }

    public function test_destroy_permanently_deletes_allowance_type(): void
    {
        $type = AllowanceType::create([
            'company_id' => $this->company->id,
            'name' => 'Rice',
            'code' => 'rice',
            'is_active' => true,
            'is_taxable' => false,
        ]);

        $this->actingAs($this->adminUser)
            ->delete("/app-settings/allowance-types/{$type->id}")
            ->assertRedirect('/app-settings/allowance-types');

        $this->assertDatabaseMissing('allowance_types', ['id' => $type->id]);
    }

    public function test_destroy_cannot_delete_other_company_allowance_type(): void
    {
        $otherCompany = Company::factory()->create();
        $type = AllowanceType::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other',
            'code' => 'other',
            'is_active' => true,
            'is_taxable' => false,
        ]);

        $this->actingAs($this->adminUser)
            ->delete("/app-settings/allowance-types/{$type->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('allowance_types', ['id' => $type->id]);
    }

    public function test_destroy_blocked_when_allowance_type_is_in_use(): void
    {
        $type = AllowanceType::create([
            'company_id' => $this->company->id,
            'name' => 'Rice',
            'code' => 'rice',
            'is_active' => true,
            'is_taxable' => false,
        ]);

        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        EmployeeAllowance::create([
            'employee_id' => $employee->id,
            'company_id' => $this->company->id,
            'allowance_type_id' => $type->id,
            'type' => 'allowance',
            'name' => 'Rice',
            'amount' => 1000,
            'is_taxable' => false,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser)
            ->delete("/app-settings/allowance-types/{$type->id}")
            ->assertRedirect('/app-settings/allowance-types');

        $this->assertDatabaseHas('allowance_types', ['id' => $type->id]);
    }

    public function test_unauthenticated_cannot_access_allowance_types(): void
    {
        $this->get('/app-settings/allowance-types')->assertRedirect('/login');
    }
}
