<?php

namespace Tests\Feature\Timekeeping;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class DtrControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id, ['timekeeping']);
    }

    private function createUserWithEmployee(?Employee $supervisor = null): array
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'supervisor_id' => $supervisor?->id,
            'user_id' => $user->id,
        ]);

        return [$user, $employee];
    }

    public function test_employee_can_view_dtr_index(): void
    {
        [$user] = $this->createUserWithEmployee();

        $this->actingAs($user)
            ->get('/timekeeping/dtr')
            ->assertInertia(fn (Assert $page) => $page->component('Timekeeping/Dtr'));
    }

    public function test_unauthenticated_cannot_view_dtr(): void
    {
        $this->get('/timekeeping/dtr')->assertRedirect('/login');
    }

    public function test_dtr_index_shows_only_own_employee_for_regular_employee(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();
        $this->createUserWithEmployee(); // unrelated employee

        $this->actingAs($user)
            ->get('/timekeeping/dtr')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Timekeeping/Dtr')
                ->count('employees', 1)
                ->where('employees.0.id', $employee->id)
            );
    }

    public function test_manager_can_see_own_and_direct_reports(): void
    {
        [$managerUser, $managerEmployee] = $this->createUserWithEmployee();
        $this->createUserWithEmployee($managerEmployee);
        $this->createUserWithEmployee(); // unrelated

        $this->actingAs($managerUser)
            ->get('/timekeeping/dtr')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Timekeeping/Dtr')
                ->count('employees', 2)
            );
    }

    public function test_employee_can_fetch_own_dtr_data(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();

        $this->actingAs($user)
            ->getJson("/api/timekeeping/dtr/data?employee_id={$employee->id}&year=2025&month=1")
            ->assertOk()
            ->assertJsonStructure([
                'month_name',
                'official_am',
                'official_pm',
                'rows',
                'total_undertime_hours',
                'total_undertime_minutes',
            ]);
    }

    public function test_dtr_data_has_31_rows_for_january(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();

        $response = $this->actingAs($user)
            ->getJson("/api/timekeeping/dtr/data?employee_id={$employee->id}&year=2025&month=1");

        $this->assertCount(31, $response->json('rows'));
    }

    public function test_employee_cannot_fetch_another_employees_dtr(): void
    {
        [$user] = $this->createUserWithEmployee();
        [, $otherEmployee] = $this->createUserWithEmployee();

        $this->actingAs($user)
            ->getJson("/api/timekeeping/dtr/data?employee_id={$otherEmployee->id}&year=2025&month=1")
            ->assertForbidden();
    }

    public function test_manager_can_fetch_direct_reports_dtr(): void
    {
        [$managerUser, $managerEmployee] = $this->createUserWithEmployee();
        [, $reportEmployee] = $this->createUserWithEmployee($managerEmployee);

        $this->actingAs($managerUser)
            ->getJson("/api/timekeeping/dtr/data?employee_id={$reportEmployee->id}&year=2025&month=1")
            ->assertOk();
    }

    public function test_manager_cannot_fetch_unrelated_employee_dtr(): void
    {
        [$managerUser] = $this->createUserWithEmployee();
        [, $otherEmployee] = $this->createUserWithEmployee();

        $this->actingAs($managerUser)
            ->getJson("/api/timekeeping/dtr/data?employee_id={$otherEmployee->id}&year=2025&month=1")
            ->assertForbidden();
    }

    public function test_dtr_data_reflects_attendance_record(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();

        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'company_id' => $this->company->id,
            'date' => '2025-01-15',
            'clock_in' => '2025-01-15 08:05:00',
            'clock_out' => '2025-01-15 17:10:00',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/timekeeping/dtr/data?employee_id={$employee->id}&year=2025&month=1");

        $row = collect($response->json('rows'))->firstWhere('day', 15);
        $this->assertSame('08:05', $row['morning_arrival']);
    }

    public function test_split_shift_populates_four_timestamps(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();

        $shiftTemplate = ShiftTemplate::factory()->withSplitShift()->create([
            'company_id' => $this->company->id,
        ]);
        $employee->update(['shift_template_id' => $shiftTemplate->id]);

        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'company_id' => $this->company->id,
            'date' => '2025-01-10',
            'clock_in' => '2025-01-10 08:00:00',
            'morning_out' => '2025-01-10 12:00:00',
            'afternoon_in' => '2025-01-10 13:00:00',
            'clock_out' => '2025-01-10 17:00:00',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/timekeeping/dtr/data?employee_id={$employee->id}&year=2025&month=1");

        $response->assertOk();
        $row = collect($response->json('rows'))->firstWhere('day', 10);
        $this->assertNotNull($row, 'Row for day 10 not found in response');
        $this->assertSame('08:00', $row['morning_arrival']);
        $this->assertSame('12:00', $row['morning_departure']);
        $this->assertSame('13:00', $row['afternoon_arrival']);
        $this->assertSame('17:00', $row['afternoon_departure']);
    }

    public function test_employee_can_download_own_dtr(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();

        $response = $this->actingAs($user)
            ->get("/timekeeping/dtr/{$employee->id}/download?year=2025&month=1");

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower($response->headers->get('Content-Type') ?? ''));
    }

    public function test_employee_cannot_download_another_employees_dtr(): void
    {
        [$user] = $this->createUserWithEmployee();
        [, $otherEmployee] = $this->createUserWithEmployee();

        $this->actingAs($user)
            ->get("/timekeeping/dtr/{$otherEmployee->id}/download?year=2025&month=1")
            ->assertForbidden();
    }

    public function test_validation_rejects_missing_parameters(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();

        $this->actingAs($user)
            ->getJson("/api/timekeeping/dtr/data?employee_id={$employee->id}")
            ->assertUnprocessable();
    }
}
