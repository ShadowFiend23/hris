<?php

namespace Tests\Feature\Payroll;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Payroll\Models\PayrollItem;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Payroll\Models\PayrollSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class EmployeePayslipTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    private PayrollSetting $setting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id, ['payroll']);

        $this->setting = PayrollSetting::create([
            'company_id' => $this->company->id,
            'period_type' => 'semi_monthly',
            'pay_day_1' => 15,
            'pay_day_2' => 30,
            'work_days_per_month' => 26,
            'is_active' => true,
        ]);
    }

    // ───── Helpers ─────────────────────────────────────────────────

    private function employeeUserWithPermissions(array $permSlugs): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $role = Role::create(['name' => 'Test '.uniqid(), 'slug' => 'test-'.uniqid()]);

        foreach ($permSlugs as $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'slug' => $slug, 'group' => 'payroll']
            );
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        Employee::factory()->create([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
        ]);

        return $user->fresh('roles.permissions', 'employee');
    }

    private function createFinalizedPeriod(string $startDate, string $endDate, string $payDate): PayrollPeriod
    {
        return PayrollPeriod::create([
            'company_id' => $this->company->id,
            'payroll_setting_id' => $this->setting->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'pay_date' => $payDate,
            'status' => 'finalized',
        ]);
    }

    private function createPayrollItem(PayrollPeriod $period, int $employeeId): PayrollItem
    {
        return PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employeeId,
            'basic_pay' => 19000.00,
            'gross_pay' => 19000.00,
            'total_deductions' => 1500.00,
            'net_pay' => 17500.00,
            'total_hours' => 88.00,
            'days_worked' => 11,
            'days_absent' => 0,
            'minutes_late' => 0,
            'status' => 'finalized',
        ]);
    }

    // ───── My Payslips index page ───────────────────────────────────

    public function test_employee_can_view_my_payslips_page(): void
    {
        $user = $this->employeeUserWithPermissions(['payroll.view_own']);
        $period = $this->createFinalizedPeriod('2026-01-01', '2026-01-15', '2026-01-15');
        $this->createPayrollItem($period, $user->employee->id);

        $this->actingAs($user)
            ->get('/payroll/my-payslips')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/MyPayslips')
                ->has('periods', 1)
                ->has('latestPayslip')
            );
    }

    public function test_my_payslips_shows_empty_state_when_no_payslips(): void
    {
        $user = $this->employeeUserWithPermissions(['payroll.view_own']);

        $this->actingAs($user)
            ->get('/payroll/my-payslips')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/MyPayslips')
                ->has('periods', 0)
                ->where('latestPayslip', null)
            );
    }

    public function test_my_payslips_shows_most_recent_period_by_default(): void
    {
        $user = $this->employeeUserWithPermissions(['payroll.view_own']);
        $empId = $user->employee->id;

        $older = $this->createFinalizedPeriod('2026-01-01', '2026-01-15', '2026-01-15');
        $newer = $this->createFinalizedPeriod('2026-01-16', '2026-01-31', '2026-01-31');
        $this->createPayrollItem($older, $empId);
        $newerItem = $this->createPayrollItem($newer, $empId);

        $this->actingAs($user)
            ->get('/payroll/my-payslips')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/MyPayslips')
                ->has('periods', 2)
                ->where('latestPayslip.id', $newerItem->id)
            );
    }

    public function test_guest_is_redirected_from_my_payslips(): void
    {
        $this->get('/payroll/my-payslips')
            ->assertRedirect('/login');
    }

    public function test_user_without_payroll_view_own_cannot_access_my_payslips(): void
    {
        $user = $this->employeeUserWithPermissions([]);

        $this->actingAs($user)
            ->get('/payroll/my-payslips')
            ->assertStatus(403);
    }

    // ───── JSON fetch endpoint ─────────────────────────────────────

    public function test_employee_can_fetch_own_payslip_json(): void
    {
        $user = $this->employeeUserWithPermissions(['payroll.view_own']);
        $period = $this->createFinalizedPeriod('2026-01-01', '2026-01-15', '2026-01-15');
        $item = $this->createPayrollItem($period, $user->employee->id);

        $this->actingAs($user)
            ->getJson("/api/payroll/my-payslips/{$item->id}")
            ->assertOk()
            ->assertJsonPath('id', $item->id)
            ->assertJsonPath('net_pay', 17500);
    }

    public function test_employee_cannot_fetch_another_employees_payslip(): void
    {
        $user = $this->employeeUserWithPermissions(['payroll.view_own']);
        $other = $this->employeeUserWithPermissions(['payroll.view_own']);

        $period = $this->createFinalizedPeriod('2026-01-01', '2026-01-15', '2026-01-15');
        $otherItem = $this->createPayrollItem($period, $other->employee->id);

        $this->actingAs($user)
            ->getJson("/api/payroll/my-payslips/{$otherItem->id}")
            ->assertStatus(403);
    }

    public function test_user_without_employee_record_cannot_fetch_payslip(): void
    {
        // User has the permission but no linked Employee record.
        $userWithoutEmployee = User::factory()->create(['company_id' => $this->company->id]);
        $role = Role::create(['name' => 'Test '.uniqid(), 'slug' => 'test-'.uniqid()]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'payroll.view_own'],
            ['name' => 'View Own Payslip', 'slug' => 'payroll.view_own', 'group' => 'payroll']
        );
        $role->permissions()->attach($permission->id);
        $userWithoutEmployee->roles()->attach($role->id);

        // Create a valid payslip owned by a different employee.
        $other = $this->employeeUserWithPermissions(['payroll.view_own']);
        $period = $this->createFinalizedPeriod('2026-01-01', '2026-01-15', '2026-01-15');
        $item = $this->createPayrollItem($period, $other->employee->id);

        $this->actingAs($userWithoutEmployee)
            ->getJson("/api/payroll/my-payslips/{$item->id}")
            ->assertStatus(403);
    }
}
