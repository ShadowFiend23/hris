<?php

namespace Tests\Feature\Payroll;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class PayrollRbacTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id, ['payroll']);
    }

    /**
     * Creates a user with the given permission slugs attached via a fresh role.
     *
     * @param  list<string>  $permissionSlugs
     */
    private function userWithPermissions(array $permissionSlugs): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $role = Role::create(['name' => 'Test Role '.uniqid(), 'slug' => 'test-'.uniqid()]);

        foreach ($permissionSlugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'slug' => $slug, 'group' => 'payroll']);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user->load('roles.permissions');
    }

    // ───────── Payroll Periods ─────────

    public function test_admin_can_view_payroll_periods(): void
    {
        $user = $this->userWithPermissions(['payroll.view_all']);

        $this->actingAs($user)->get('/payroll/periods')
            ->assertInertia(fn (Assert $page) => $page->component('Payroll/Periods'));
    }

    public function test_employee_with_only_view_own_cannot_view_all_periods(): void
    {
        $user = $this->userWithPermissions(['payroll.view_own']);

        // view_own does NOT grant access to the periods index (view_all is required)
        $this->actingAs($user)->get('/payroll/periods')->assertStatus(403);
    }

    public function test_unauthenticated_cannot_view_periods(): void
    {
        $this->get('/payroll/periods')->assertRedirect('/login');
    }

    // ───────── Creating Periods (payroll.run) ─────────

    public function test_user_with_payroll_run_can_create_period(): void
    {
        $user = $this->userWithPermissions(['payroll.run']);

        $response = $this->actingAs($user)->post('/payroll/periods', [
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-15',
            'pay_date' => '2025-06-20',
            'payroll_setting_id' => null, // no setting; form request will reject
        ]);

        // Without a valid payroll_setting_id it should redirect with errors, not 403
        $response->assertSessionHasErrors(['payroll_setting_id']);
    }

    public function test_user_without_payroll_run_cannot_create_period(): void
    {
        $user = $this->userWithPermissions(['payroll.view_all']);

        $this->actingAs($user)->post('/payroll/periods', [
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-15',
            'pay_date' => '2025-06-20',
        ])->assertStatus(403);
    }

    // ───────── Payroll Settings ─────────

    public function test_admin_with_payroll_settings_can_view_settings(): void
    {
        $user = $this->userWithPermissions(['payroll.settings']);

        $this->actingAs($user)->get('/app-settings/payroll')
            ->assertInertia(fn (Assert $page) => $page->component('Payroll/Settings/PayrollSettings'));
    }

    public function test_manager_without_payroll_settings_cannot_view_settings(): void
    {
        $user = $this->userWithPermissions(['payroll.view_all']);

        $this->actingAs($user)->get('/app-settings/payroll')->assertStatus(403);
    }

    // ───────── Holidays ─────────

    public function test_user_with_holidays_permission_can_view_holidays(): void
    {
        $user = $this->userWithPermissions(['payroll.holidays']);

        $this->actingAs($user)->get('/app-settings/holidays')
            ->assertInertia(fn (Assert $page) => $page->component('Payroll/Settings/Holidays'));
    }

    public function test_user_without_holidays_permission_cannot_view_holidays(): void
    {
        $user = $this->userWithPermissions(['payroll.view_all']);

        $this->actingAs($user)->get('/app-settings/holidays')->assertStatus(403);
    }

    // ───────── Loans ─────────

    public function test_user_with_loans_permission_can_view_loans(): void
    {
        $user = $this->userWithPermissions(['payroll.loans']);

        $this->actingAs($user)->get('/loans')
            ->assertInertia(fn (Assert $page) => $page->component('Payroll/Loans'));
    }

    public function test_user_without_loans_permission_cannot_view_loans(): void
    {
        $user = $this->userWithPermissions(['payroll.view_all']);

        $this->actingAs($user)->get('/loans')->assertStatus(403);
    }
}
