<?php

namespace Tests\Feature\Timekeeping;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class AttendanceEmployeeFilterTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id, ['timekeeping']);
    }

    private function createUserWithEmployee(): array
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
        ]);

        return [$user, $employee];
    }

    public function test_history_returns_own_records_without_employee_id(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'company_id' => $this->company->id]);

        $this->actingAs($user)
            ->getJson('/api/timekeeping/attendance/history')
            ->assertOk()
            ->assertJsonPath('data.0.employee_id', $employee->id);
    }

    public function test_history_returns_target_employee_records_when_employee_id_provided(): void
    {
        [$requester] = $this->createUserWithEmployee();
        [, $targetEmployee] = $this->createUserWithEmployee();
        AttendanceRecord::factory()->create(['employee_id' => $targetEmployee->id, 'company_id' => $this->company->id]);

        $this->actingAs($requester)
            ->getJson('/api/timekeeping/attendance/history?employee_id='.$targetEmployee->id)
            ->assertOk()
            ->assertJsonPath('data.0.employee_id', $targetEmployee->id);
    }

    public function test_history_rejects_employee_from_different_company(): void
    {
        [$user] = $this->createUserWithEmployee();

        $otherCompany = Company::factory()->create();
        $otherEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($user)
            ->getJson('/api/timekeeping/attendance/history?employee_id='.$otherEmployee->id)
            ->assertForbidden();
    }

    public function test_summary_returns_own_summary_without_employee_id(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();
        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->actingAs($user)
            ->getJson('/api/timekeeping/attendance/summary?start_date='.now()->subMonth()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.present_days', 1);
    }

    public function test_summary_returns_target_employee_summary_when_employee_id_provided(): void
    {
        [$requester] = $this->createUserWithEmployee();
        [, $targetEmployee] = $this->createUserWithEmployee();
        AttendanceRecord::factory()->create([
            'employee_id' => $targetEmployee->id,
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->actingAs($requester)
            ->getJson('/api/timekeeping/attendance/summary?start_date='.now()->subMonth()->toDateString().'&end_date='.now()->toDateString().'&employee_id='.$targetEmployee->id)
            ->assertOk()
            ->assertJsonPath('data.present_days', 1);
    }

    public function test_summary_rejects_employee_from_different_company(): void
    {
        [$user] = $this->createUserWithEmployee();

        $otherCompany = Company::factory()->create();
        $otherEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($user)
            ->getJson('/api/timekeeping/attendance/summary?start_date='.now()->subMonth()->toDateString().'&end_date='.now()->toDateString().'&employee_id='.$otherEmployee->id)
            ->assertForbidden();
    }
}
