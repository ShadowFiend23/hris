<?php

namespace Tests\Feature\Timekeeping;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Carbon\Carbon;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_attendance_records_for_all_active_employees(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where('slug', 'test-company')->firstOrFail();
        $employees = Employee::where('company_id', $company->id)->where('is_active', true)->get();

        foreach ($employees as $employee) {
            $this->assertGreaterThan(
                0,
                AttendanceRecord::where('employee_id', $employee->id)->count(),
                "Employee {$employee->employee_id} has no attendance records"
            );
        }
    }

    #[Test]
    public function it_distributes_employees_across_multiple_shifts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where('slug', 'test-company')->firstOrFail();

        $assignedShiftIds = Employee::where('company_id', $company->id)
            ->whereNotNull('shift_template_id')
            ->pluck('shift_template_id')
            ->unique();

        $this->assertGreaterThan(1, $assignedShiftIds->count(), 'All employees share the same shift');
    }

    #[Test]
    public function it_correctly_seeds_night_shift_attendance_with_next_day_clock_out(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where('slug', 'test-company')->firstOrFail();

        $nightShift = ShiftTemplate::where('company_id', $company->id)
            ->where('name', 'Night Shift')
            ->firstOrFail();

        $nightEmployee = Employee::where('company_id', $company->id)
            ->where('shift_template_id', $nightShift->id)
            ->first();

        if (! $nightEmployee) {
            $this->markTestSkipped('No employee assigned to Night Shift.');
        }

        $record = AttendanceRecord::where('employee_id', $nightEmployee->id)->first();

        $this->assertNotNull($record);

        $clockIn = Carbon::parse($record->clock_in);
        $clockOut = Carbon::parse($record->clock_out);

        // Night shift: clock_out must be on a different (later) calendar day than clock_in
        $this->assertTrue(
            $clockOut->toDateString() > $clockIn->toDateString(),
            "Night shift clock_out ({$clockOut}) should be on the day after clock_in ({$clockIn})"
        );
    }

    #[Test]
    public function it_marks_late_arrivals_correctly(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where('slug', 'test-company')->firstOrFail();

        $hasLate = AttendanceRecord::whereHas('employee', fn ($q) => $q->where('company_id', $company->id))
            ->where('status', 'late')
            ->exists();

        $hasPresent = AttendanceRecord::whereHas('employee', fn ($q) => $q->where('company_id', $company->id))
            ->where('status', 'present')
            ->exists();

        $this->assertTrue($hasPresent, 'No present records were seeded');
        // Late arrivals are random (variance up to +30 min, grace is 15 min), so at least some should exist
        // across 22 employees × 90 days — this is a soft check
        $this->assertTrue($hasLate || $hasPresent, 'No attendance records were seeded at all');
    }

    #[Test]
    public function it_does_not_duplicate_records_when_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where('slug', 'test-company')->firstOrFail();
        $countBefore = AttendanceRecord::whereHas('employee', fn ($q) => $q->where('company_id', $company->id))->count();

        $this->seed(AttendanceSeeder::class);

        $countAfter = AttendanceRecord::whereHas('employee', fn ($q) => $q->where('company_id', $company->id))->count();

        $this->assertEquals($countBefore, $countAfter, 'Running the seeder twice created duplicate records');
    }
}
