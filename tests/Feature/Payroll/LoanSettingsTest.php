<?php

namespace Tests\Feature\Payroll;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Payroll\Models\LoanType;
use App\Modules\Payroll\Models\PayrollSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class LoanSettingsTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->company = Company::factory()->create(['loans_enabled' => true]);
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

    public function test_loan_settings_page_passes_loans_enabled_true(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/app-settings/loan-types')
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRSettings/LoanTypes')
                ->where('loansEnabled', true)
            );
    }

    public function test_loan_settings_page_passes_loans_enabled_false_when_disabled(): void
    {
        $this->company->update(['loans_enabled' => false]);

        $this->actingAs($this->adminUser)
            ->get('/app-settings/loan-types')
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRSettings/LoanTypes')
                ->where('loansEnabled', false)
            );
    }

    public function test_toggle_enables_loans_when_disabled(): void
    {
        $this->company->update(['loans_enabled' => false]);

        $this->actingAs($this->adminUser)
            ->patch('/app-settings/loan-settings/toggle')
            ->assertRedirect('/app-settings/loan-types');

        $this->assertTrue($this->company->fresh()->loans_enabled);
    }

    public function test_toggle_disables_loans_when_enabled(): void
    {
        $this->actingAs($this->adminUser)
            ->patch('/app-settings/loan-settings/toggle')
            ->assertRedirect('/app-settings/loan-types');

        $this->assertFalse($this->company->fresh()->loans_enabled);
    }

    public function test_toggle_only_affects_own_company(): void
    {
        $otherCompany = Company::factory()->create(['loans_enabled' => true]);

        $this->actingAs($this->adminUser)
            ->patch('/app-settings/loan-settings/toggle');

        $this->assertTrue($otherCompany->fresh()->loans_enabled);
    }

    public function test_unauthenticated_cannot_access_loan_settings(): void
    {
        $this->get('/app-settings/loan-types')->assertRedirect('/login');
    }

    public function test_unauthenticated_cannot_toggle_loans(): void
    {
        $this->patch('/app-settings/loan-settings/toggle')->assertRedirect('/login');
    }

    public function test_loan_types_index_excludes_other_company_types(): void
    {
        $otherCompany = Company::factory()->create();
        $defaultType = LoanType::create(['company_id' => null, 'name' => 'SSS Loan', 'code' => 'sss_loan', 'is_active' => true]);
        $ownType = LoanType::create(['company_id' => $this->company->id, 'name' => 'Staff Loan', 'code' => 'staff_loan', 'is_active' => true]);
        LoanType::create(['company_id' => $otherCompany->id, 'name' => 'Other Loan', 'code' => 'other_loan', 'is_active' => true]);

        $this->actingAs($this->adminUser)
            ->get('/app-settings/loan-types')
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRSettings/LoanTypes')
                ->has('loanTypes', 2)
                ->where('loanTypes.0.id', $defaultType->id)
                ->where('loanTypes.1.id', $ownType->id)
            );
    }

    public function test_loans_page_returns_403_when_loans_disabled(): void
    {
        $this->company->update(['loans_enabled' => false]);

        $this->actingAs($this->adminUser)
            ->get('/loans')
            ->assertForbidden();
    }

    public function test_loans_page_accessible_when_loans_enabled(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/loans')
            ->assertOk();
    }

    public function test_shared_loans_enabled_prop_is_true_by_default(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/app-settings/loan-types')
            ->assertInertia(fn (Assert $page) => $page
                ->where('loansEnabled', true)
            );
    }

    public function test_shared_loans_enabled_prop_is_false_when_disabled(): void
    {
        $this->company->update(['loans_enabled' => false]);

        $this->actingAs($this->adminUser)
            ->get('/app-settings/loan-types')
            ->assertInertia(fn (Assert $page) => $page
                ->where('loansEnabled', false)
            );
    }

    public function test_store_creates_loan_type(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/app-settings/loan-types', [
                'name' => 'Calamity Loan',
                'code' => 'calamity_loan',
                'description' => 'For calamity victims',
                'max_amount' => 50000,
            ])
            ->assertRedirect('/app-settings/loan-types');

        $this->assertDatabaseHas('loan_types', [
            'company_id' => $this->company->id,
            'name' => 'Calamity Loan',
            'code' => 'calamity_loan',
            'is_active' => true,
        ]);
    }

    public function test_update_loan_type_without_code(): void
    {
        $loanType = LoanType::create([
            'company_id' => $this->company->id,
            'name' => 'Staff Loan',
            'code' => 'staff_loan',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser)
            ->put("/app-settings/loan-types/{$loanType->id}", [
                'name' => 'Updated Staff Loan',
                'description' => 'Updated description',
                'max_amount' => 30000,
            ])
            ->assertRedirect('/app-settings/loan-types');

        $this->assertDatabaseHas('loan_types', [
            'id' => $loanType->id,
            'name' => 'Updated Staff Loan',
            'code' => 'staff_loan',
        ]);
    }

    public function test_destroy_permanently_deletes_loan_type(): void
    {
        $loanType = LoanType::create([
            'company_id' => $this->company->id,
            'name' => 'Staff Loan',
            'code' => 'staff_loan',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser)
            ->delete("/app-settings/loan-types/{$loanType->id}")
            ->assertRedirect('/app-settings/loan-types');

        $this->assertDatabaseMissing('loan_types', ['id' => $loanType->id]);
    }

    public function test_destroy_cannot_delete_other_company_loan_type(): void
    {
        $otherCompany = Company::factory()->create();
        $loanType = LoanType::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Loan',
            'code' => 'other_loan',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser)
            ->delete("/app-settings/loan-types/{$loanType->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('loan_types', ['id' => $loanType->id]);
    }
}
