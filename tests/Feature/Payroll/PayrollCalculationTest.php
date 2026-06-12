<?php

namespace Tests\Feature\Payroll;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Position;
use App\Modules\Payroll\Models\Holiday;
use App\Modules\Payroll\Models\PayrollItem;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Payroll\Models\PayrollSetting;
use App\Modules\Payroll\Services\PayrollCalculationService;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\LeaveType;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use App\Modules\Timekeeping\Models\WorkPolicy;
use Carbon\Carbon;
use Database\Seeders\ContributionBracketsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculationTest extends TestCase
{
    use RefreshDatabase;

    private const CUTOFF_START = '2026-06-01'; // Monday

    private const CUTOFF_END = '2026-06-15';

    private Company $company;

    private Department $department;

    private Position $position;

    private ShiftTemplate $shift;

    private PayrollSetting $setting;

    private PayrollCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Pin "today" after the test cut-off so absence counting (capped at today) is deterministic.
        Carbon::setTestNow('2026-06-20');
        $this->seed(ContributionBracketsSeeder::class);

        $this->company = Company::factory()->create(['loans_enabled' => false]);
        $this->department = Department::factory()->create(['company_id' => $this->company->id]);
        $this->position = Position::factory()->create(['department_id' => $this->department->id]);

        $this->shift = ShiftTemplate::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Regular Day Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'break_duration' => 60,
            'work_days' => [1, 2, 3, 4, 5],
        ]);

        WorkPolicy::create([
            'company_id' => $this->company->id,
            'standard_hours_per_day' => 8,
            'standard_hours_per_week' => 40,
            'grace_period_minutes' => 5,
            'late_threshold_minutes' => 15,
            'weekday_overtime_rate' => 1.25,
            'weekend_overtime_rate' => 1.69,
            'holiday_overtime_rate' => 2.60,
            'night_differential_rate' => 0.10,
            'regular_holiday_rate' => 2.00,
            'special_holiday_rate' => 1.30,
            'rest_day_rate' => 1.30,
            'is_active' => true,
        ]);

        $this->setting = PayrollSetting::create([
            'company_id' => $this->company->id,
            'period_type' => 'semi_monthly',
            'pay_day_1' => 15,
            'pay_day_2' => 30,
            'work_days_per_month' => 26,
            'cutoff_offset_days' => 0,
            'night_differential_rate' => 0.10,
            'is_active' => true,
        ]);

        $this->service = app(PayrollCalculationService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeEmployee(float $salary, string $salaryType = 'monthly'): Employee
    {
        return Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'salary' => $salary,
            'salary_type' => $salaryType,
            'shift_template_id' => $this->shift->id,
            'is_active' => true,
            'employment_status' => 'active',
        ]);
    }

    private function attendance(
        Employee $e,
        string $date,
        string $status = 'present',
        string $in = '08:00:00',
        string $out = '17:00:00',
        float $hours = 8.0
    ): void {
        AttendanceRecord::create([
            'employee_id' => $e->id,
            'company_id' => $e->company_id,
            'date' => $date,
            'clock_in' => "$date $in",
            'clock_out' => "$date $out",
            'total_hours' => $hours,
            'break_duration' => 60,
            'status' => $status,
            'source' => 'manual',
        ]);
    }

    /**
     * Fill every weekday in the cut-off with a present record, skipping given dates.
     *
     * @param  array<int, string>  $skip
     */
    private function fillWorkdays(Employee $e, array $skip = [], string $status = 'present'): void
    {
        $day = Carbon::parse(self::CUTOFF_START);
        $end = Carbon::parse(self::CUTOFF_END);

        while ($day->lte($end)) {
            $dateStr = $day->toDateString();
            $isWeekday = (int) $day->format('N') <= 5;

            if ($isWeekday && ! in_array($dateStr, $skip, true)) {
                $this->attendance($e, $dateStr, $status);
            }

            $day->addDay();
        }
    }

    private function compute(Employee $e): PayrollItem
    {
        $period = PayrollPeriod::create([
            'company_id' => $this->company->id,
            'payroll_setting_id' => $this->setting->id,
            'start_date' => self::CUTOFF_START,
            'end_date' => self::CUTOFF_END,
            'cutoff_start_date' => self::CUTOFF_START,
            'cutoff_end_date' => self::CUTOFF_END,
            'pay_date' => self::CUTOFF_END,
            'status' => 'draft',
        ]);

        return $this->service->computeForEmployee($period, $e)->fresh(['earnings', 'deductions']);
    }

    private function deduction(PayrollItem $item, string $type): ?float
    {
        $d = $item->deductions->firstWhere('type', $type);

        return $d ? (float) $d->amount : null;
    }

    private function earning(PayrollItem $item, string $type): ?float
    {
        $e = $item->earnings->firstWhere('type', $type);

        return $e ? (float) $e->amount : null;
    }

    public function test_fully_worked_month_has_no_absence_and_correct_contributions(): void
    {
        $employee = $this->makeEmployee(26000);
        $this->fillWorkdays($employee);

        $item = $this->compute($employee);

        $this->assertSame(13000.0, (float) $item->basic_pay);
        $this->assertSame(0.0, (float) $item->days_absent);
        $this->assertNull($this->deduction($item, 'absence'));
        $this->assertSame(650.0, $this->deduction($item, 'sss'));
        $this->assertSame(325.0, $this->deduction($item, 'philhealth'));
        $this->assertSame(100.0, $this->deduction($item, 'pagibig'));
        // Taxable ₱11,925 → 15% over ₱10,417 floor = ₱226.20
        $this->assertEqualsWithDelta(226.20, $this->deduction($item, 'withholding_tax'), 0.5);
    }

    public function test_absence_is_deducted_exactly_once(): void
    {
        $employee = $this->makeEmployee(26000);
        // Skip one weekday (2026-06-15, a Monday) → one unpaid absence.
        $this->fillWorkdays($employee, ['2026-06-15']);

        $item = $this->compute($employee);

        // Basic stays full (not reduced) and the single absence deduction docks one day.
        $this->assertSame(13000.0, (float) $item->basic_pay);
        $this->assertSame(1.0, (float) $item->days_absent);
        $this->assertSame(1000.0, $this->deduction($item, 'absence'));
    }

    public function test_late_days_are_not_treated_as_absent(): void
    {
        $employee = $this->makeEmployee(26000);
        // All weekdays present, but mark three as 'late' (clocked in on time → no undertime).
        $this->fillWorkdays($employee);
        AttendanceRecord::where('employee_id', $employee->id)
            ->whereIn('date', ['2026-06-02', '2026-06-03', '2026-06-04'])
            ->update(['status' => 'late']);

        $item = $this->compute($employee);

        $this->assertSame(0.0, (float) $item->days_absent);
        $this->assertNull($this->deduction($item, 'absence'));
    }

    public function test_undertime_is_docked_as_minutes_not_a_whole_day(): void
    {
        $employee = $this->makeEmployee(26000);
        $this->fillWorkdays($employee, ['2026-06-10']);
        // 2026-06-10: clocked out 2h early → 120 min undertime, still a worked day.
        $this->attendance($employee, '2026-06-10', 'present', '08:00:00', '15:00:00', 6.0);

        $item = $this->compute($employee);

        $this->assertSame(0.0, (float) $item->days_absent);
        $this->assertNull($this->deduction($item, 'absence'));
        $this->assertSame(120, (int) $item->minutes_late);
        // 120 min × (₱1000/day ÷ 8h) = 2h × ₱125 = ₱250
        $this->assertEqualsWithDelta(250.0, $this->deduction($item, 'tardiness'), 0.01);
    }

    public function test_regular_holiday_worked_adds_premium_only(): void
    {
        $employee = $this->makeEmployee(26000);
        $this->fillWorkdays($employee); // includes 2026-06-10 worked 8h
        Holiday::create([
            'company_id' => null,
            'name' => 'Test Regular Holiday',
            'date' => '2026-06-10',
            'type' => 'regular',
            'is_recurring' => false,
            'is_active' => true,
        ]);

        $item = $this->compute($employee);

        // Premium only: hourly ₱125 × 8h × (2.0 − 1.0) = ₱1,000 (NOT ₱2,000 stacked on basic).
        $this->assertSame(1000.0, $this->earning($item, 'regular_holiday'));
        $this->assertSame(13000.0, (float) $item->basic_pay);
    }

    public function test_special_holiday_worked_adds_thirty_percent_premium(): void
    {
        $employee = $this->makeEmployee(26000);
        $this->fillWorkdays($employee);
        Holiday::create([
            'company_id' => null,
            'name' => 'Test Special Holiday',
            'date' => '2026-06-10',
            'type' => 'special',
            'is_recurring' => false,
            'is_active' => true,
        ]);

        $item = $this->compute($employee);

        // Premium only: ₱125 × 8h × (1.30 − 1.0) = ₱300
        $this->assertSame(300.0, $this->earning($item, 'special_holiday'));
    }

    public function test_paid_leave_is_not_docked(): void
    {
        $employee = $this->makeEmployee(26000);
        $this->fillWorkdays($employee, ['2026-06-10']); // no attendance on the leave day

        $type = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Vacation Leave',
            'code' => 'VL',
            'days_per_year' => 15,
            'is_paid' => true,
            'requires_approval' => true,
            'is_active' => true,
        ]);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'total_days' => 1,
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        $item = $this->compute($employee);

        $this->assertSame(0.0, (float) $item->days_absent);
        $this->assertNull($this->deduction($item, 'absence'));
    }

    public function test_unpaid_leave_is_docked(): void
    {
        $employee = $this->makeEmployee(26000);
        $this->fillWorkdays($employee, ['2026-06-10']);

        $type = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Leave Without Pay',
            'code' => 'LWOP',
            'days_per_year' => 0,
            'is_paid' => false,
            'requires_approval' => true,
            'is_active' => true,
        ]);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'total_days' => 1,
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        $item = $this->compute($employee);

        $this->assertSame(1.0, (float) $item->days_absent);
        $this->assertSame(1000.0, $this->deduction($item, 'absence'));
    }

    public function test_daily_paid_employee_uses_monthly_equivalent_basic(): void
    {
        // Daily rate ₱1,000 → monthly-equivalent ₱26,000 → ₱13,000 per cut-off.
        $employee = $this->makeEmployee(1000, 'daily');
        $this->fillWorkdays($employee);

        $item = $this->compute($employee);

        $this->assertSame(13000.0, (float) $item->basic_pay);
        $this->assertSame(0.0, (float) $item->days_absent);
    }

    public function test_run_leaves_period_for_review_then_finalize_locks_it(): void
    {
        // Running computes payslips and moves the period to "review" (NOT finalized).
        $employee = $this->makeEmployee(26000);
        $this->fillWorkdays($employee);

        $period = PayrollPeriod::create([
            'company_id' => $this->company->id,
            'payroll_setting_id' => $this->setting->id,
            'start_date' => self::CUTOFF_START,
            'end_date' => self::CUTOFF_END,
            'cutoff_start_date' => self::CUTOFF_START,
            'cutoff_end_date' => self::CUTOFF_END,
            'pay_date' => self::CUTOFF_END,
            'status' => 'draft',
        ]);

        $this->service->runPayrollForPeriod($period);
        $this->assertSame('review', $period->fresh()->status);
        $this->assertGreaterThan(0, $period->items()->count());

        // Finalizing is a separate step that locks the period.
        $this->service->finalizePeriod($period);
        $this->assertSame('finalized', $period->fresh()->status);
    }

    public function test_night_shift_late_arrival_is_docked_as_undertime(): void
    {
        // M1: undertime must be computed for crossing-midnight shifts.
        $nightShift = ShiftTemplate::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Night Shift',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'break_duration' => 60,
            'work_days' => [1, 2, 3, 4, 5, 6],
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'salary' => 26000,
            'salary_type' => 'monthly',
            'shift_template_id' => $nightShift->id,
        ]);

        // Clocked in 22:30 (30 min late), out 06:00 next day.
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'date' => '2026-06-09',
            'clock_in' => '2026-06-09 22:30:00',
            'clock_out' => '2026-06-10 06:00:00',
            'total_hours' => 6.5,
            'break_duration' => 60,
            'status' => 'late',
            'source' => 'manual',
        ]);

        $item = $this->compute($employee);

        $this->assertSame(30, (int) $item->minutes_late);
        $this->assertNotNull($this->deduction($item, 'tardiness'));
    }

    public function test_night_differential_uses_admin_configured_rate(): void
    {
        // Admin sets 15% in Payroll Settings; the engine must honor it over the work policy's 10%.
        $this->setting->update(['night_differential_rate' => 0.15]);

        $nightShift = ShiftTemplate::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Night Shift',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'break_duration' => 60,
            'work_days' => [1, 2, 3, 4, 5, 6],
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'salary' => 26000,
            'salary_type' => 'monthly',
            'shift_template_id' => $nightShift->id,
        ]);
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'date' => '2026-06-09',
            'clock_in' => '2026-06-09 22:00:00',
            'clock_out' => '2026-06-10 06:00:00',
            'total_hours' => 7.0,
            'break_duration' => 60,
            'status' => 'present',
        ]);

        $item = $this->compute($employee)->fresh(['earnings']);
        $nd = $item->earnings->firstWhere('type', 'night_differential');

        $this->assertNotNull($nd);
        // amount ÷ (ND hours × hourly ₱125) should equal the configured 15%, not 10%.
        $impliedRate = (float) $nd->amount / ((float) $nd->hours * 125);
        $this->assertEqualsWithDelta(0.15, $impliedRate, 0.001);
    }

    public function test_rest_day_worked_pays_full_premium(): void
    {
        // M2: 2026-06-06 is a Saturday — a rest day for the Mon–Fri shift.
        $employee = $this->makeEmployee(26000);
        $this->attendance($employee, '2026-06-06', 'present', '08:00:00', '17:00:00', 8.0);

        $item = $this->compute($employee);

        // hourly ₱125 × 8h × 1.30 = ₱1,300
        $this->assertSame(1300.0, $this->earning($item, 'rest_day'));
    }

    public function test_rest_day_on_regular_holiday_compounds_to_260(): void
    {
        $employee = $this->makeEmployee(26000);
        $this->attendance($employee, '2026-06-06', 'present', '08:00:00', '17:00:00', 8.0);
        Holiday::create([
            'company_id' => null,
            'name' => 'Test Regular Holiday',
            'date' => '2026-06-06',
            'type' => 'regular',
            'is_recurring' => false,
            'is_active' => true,
        ]);

        $item = $this->compute($employee);

        // ₱125 × 8h × (2.00 × 1.30 = 2.60) = ₱2,600
        $this->assertSame(2600.0, $this->earning($item, 'rest_day'));
    }

    public function test_future_days_are_not_counted_as_absent(): void
    {
        // M3: with "today" mid cut-off, only elapsed work days are docked.
        Carbon::setTestNow('2026-06-08');
        $employee = $this->makeEmployee(26000); // no attendance at all

        $item = $this->compute($employee);

        // Weekdays 06-01..06-08 = Mon 1, Tue 2, Wed 3, Thu 4, Fri 5, Mon 8 = 6 days.
        // Future weekdays 9/10/11/12/15 must NOT be docked.
        $this->assertSame(6.0, (float) $item->days_absent);
    }

    public function test_overtime_compounds_on_rest_and_holiday(): void
    {
        // L1: rest-day OT 169%, regular-holiday OT 260%.
        $employee = $this->makeEmployee(26000);
        $this->fillWorkdays($employee);

        OvertimeRecord::create([
            'employee_id' => $employee->id, 'company_id' => $this->company->id,
            'date' => '2026-06-06', 'hours' => 3, 'overtime_type' => 'weekend',
            'pay_rate_multiplier' => 1.69, 'status' => 'approved',
        ]);
        OvertimeRecord::create([
            'employee_id' => $employee->id, 'company_id' => $this->company->id,
            'date' => '2026-06-09', 'hours' => 2, 'overtime_type' => 'holiday',
            'pay_rate_multiplier' => 2.60, 'status' => 'approved',
        ]);

        $item = $this->compute($employee);

        // hourly ₱125 → rest OT 3×125×1.69 = 633.75 ; holiday OT 2×125×2.60 = 650
        $this->assertSame(633.75, $this->earning($item, 'overtime_weekend'));
        $this->assertSame(650.0, $this->earning($item, 'overtime_holiday'));
    }

    public function test_half_day_counts_as_half_in_days_worked(): void
    {
        // L3: a half-day contributes 0.5 to days_worked.
        $employee = $this->makeEmployee(26000);
        $this->fillWorkdays($employee, ['2026-06-10']);
        $this->attendance($employee, '2026-06-10', 'half_day', '08:00:00', '12:00:00', 3.0);

        $item = $this->compute($employee);

        $fraction = (float) $item->days_worked - floor((float) $item->days_worked);
        $this->assertEqualsWithDelta(0.5, $fraction, 0.001);
    }
}
