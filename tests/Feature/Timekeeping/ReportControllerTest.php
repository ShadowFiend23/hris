<?php

namespace Tests\Feature\Timekeeping;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\LeaveType;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class ReportControllerTest extends TestCase
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

    public function test_leave_report_returns_requests_of_all_statuses_within_date_range(): void
    {
        [$requester] = $this->createUserWithEmployee();
        [, $employeeA] = $this->createUserWithEmployee();
        [, $employeeB] = $this->createUserWithEmployee();

        $leaveType = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Vacation Leave',
            'code' => 'VL',
            'days_per_year' => 15,
        ]);

        // Pending request inside the range.
        LeaveRequest::create([
            'employee_id' => $employeeA->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-21',
            'total_days' => 2,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Approved request inside the range.
        LeaveRequest::create([
            'employee_id' => $employeeB->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-03',
            'total_days' => 3,
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        // Outside the range — must be excluded.
        LeaveRequest::create([
            'employee_id' => $employeeA->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-11',
            'total_days' => 2,
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        $this->actingAs($requester)
            ->getJson('/api/timekeeping/reports/leave?start_date=2026-05-13&end_date=2026-06-13')
            ->assertOk()
            ->assertJsonPath('summary.total_requests', 2)
            ->assertJsonPath('summary.pending_requests', 1)
            ->assertJsonPath('summary.approved_requests', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_leave_report_can_filter_by_employee(): void
    {
        [$requester] = $this->createUserWithEmployee();
        [, $employeeA] = $this->createUserWithEmployee();
        [, $employeeB] = $this->createUserWithEmployee();

        $leaveType = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Sick Leave',
            'code' => 'SL',
            'days_per_year' => 15,
        ]);

        LeaveRequest::create([
            'employee_id' => $employeeA->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-21',
            'total_days' => 2,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        LeaveRequest::create([
            'employee_id' => $employeeB->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-22',
            'end_date' => '2026-05-23',
            'total_days' => 2,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($requester)
            ->getJson('/api/timekeeping/reports/leave?start_date=2026-05-13&end_date=2026-06-13&employee_id='.$employeeA->id)
            ->assertOk()
            ->assertJsonPath('summary.total_requests', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee.id', $employeeA->id);
    }

    public function test_overtime_report_returns_records_of_all_statuses_within_date_range(): void
    {
        [$requester] = $this->createUserWithEmployee();
        [, $employeeA] = $this->createUserWithEmployee();

        OvertimeRecord::create([
            'employee_id' => $employeeA->id,
            'company_id' => $this->company->id,
            'date' => '2026-05-20',
            'hours' => 2,
            'overtime_type' => 'weekday',
            'status' => 'pending',
        ]);

        OvertimeRecord::create([
            'employee_id' => $employeeA->id,
            'company_id' => $this->company->id,
            'date' => '2026-06-01',
            'hours' => 3,
            'overtime_type' => 'weekend',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($requester)
            ->getJson('/api/timekeeping/reports/overtime?start_date=2026-05-13&end_date=2026-06-13')
            ->assertOk()
            ->assertJsonPath('summary.total_requests', 2);

        $records = collect($response->json('data'))->flatMap(fn ($group) => $group['records']);
        $this->assertCount(2, $records);
    }
}
