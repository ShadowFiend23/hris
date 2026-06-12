<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\EmployeeSchedule;
use App\Modules\Timekeeping\Models\LeaveBalance;
use App\Modules\Timekeeping\Models\LeaveType;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use App\Modules\Timekeeping\Models\WorkPolicy;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimekeepingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->seedCompanyData($company);
        }
    }

    /**
     * Seed timekeeping data for a specific company
     */
    private function seedCompanyData(Company $company): void
    {
        // Create leave types
        $leaveTypes = $this->createLeaveTypes($company);

        // Create shift templates
        $shiftTemplates = $this->createShiftTemplates($company);

        // Create work policy
        $this->createWorkPolicy($company);

        // Initialize leave balances and assign shifts for all employees
        $employees = Employee::where('company_id', $company->id)->get();

        $defaultShift = collect($shiftTemplates)->firstWhere('name', 'Regular Day Shift');

        foreach ($employees as $employee) {
            $this->initializeLeaveBalances($employee, $leaveTypes);
            $this->assignShiftToEmployee($employee, $defaultShift);
            $this->generateYearSchedules($employee, $defaultShift);
        }
    }

    /**
     * Create leave types for a company
     */
    private function createLeaveTypes(Company $company): array
    {
        $types = [
            [
                'name' => 'Vacation Leave',
                'code' => 'VL',
                'days_per_year' => 15,
                'is_paid' => true,
                'requires_approval' => true,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'days_per_year' => 15,
                'is_paid' => true,
                'requires_approval' => true,
            ],
            [
                'name' => 'Emergency Leave',
                'code' => 'EL',
                'days_per_year' => 5,
                'is_paid' => true,
                'requires_approval' => true,
            ],
            [
                'name' => 'Bereavement Leave',
                'code' => 'BL',
                'days_per_year' => 5,
                'is_paid' => true,
                'requires_approval' => true,
            ],
        ];

        $createdTypes = [];

        foreach ($types as $type) {
            $createdTypes[] = LeaveType::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $type['code'],
                ],
                array_merge($type, ['company_id' => $company->id, 'is_active' => true])
            );
        }

        return $createdTypes;
    }

    /**
     * Create shift templates for a company
     */
    private function createShiftTemplates(Company $company): array
    {
        $templates = [
            [
                'name' => 'Morning Shift',
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
                'duration_hours' => 8,
                'break_duration' => 60,
                'work_days' => [1, 2, 3, 4, 5, 6], // Mon–Sat
            ],
            [
                'name' => 'Regular Day Shift',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'duration_hours' => 8,
                'break_duration' => 60,
                'work_days' => [1, 2, 3, 4, 5], // Mon–Fri
            ],
            [
                'name' => 'Afternoon Shift',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'duration_hours' => 8,
                'break_duration' => 60,
                'work_days' => [1, 2, 3, 4, 5, 6], // Mon–Sat
            ],
            [
                'name' => 'Night Shift',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'duration_hours' => 8,
                'break_duration' => 60,
                'work_days' => [1, 2, 3, 4, 5, 6], // Mon–Sat
            ],
            [
                'name' => 'Flexible Hours',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'duration_hours' => 8,
                'break_duration' => 60,
                'work_days' => [1, 2, 3, 4, 5], // Mon–Fri
            ],
        ];

        $createdTemplates = [];

        foreach ($templates as $template) {
            $shift = ShiftTemplate::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $template['name'],
                ],
                array_merge($template, ['company_id' => $company->id, 'is_active' => true])
            );

            // Ensure work_days is always up-to-date even for pre-existing records
            if ($shift->work_days === null) {
                $shift->update(['work_days' => $template['work_days']]);
            }

            $createdTemplates[] = $shift->fresh();
        }

        return $createdTemplates;
    }

    /**
     * Create work policy for a company
     */
    private function createWorkPolicy(Company $company): WorkPolicy
    {
        return WorkPolicy::firstOrCreate(
            [
                'company_id' => $company->id,
            ],
            [
                'company_id' => $company->id,
                'standard_hours_per_day' => 8,
                'standard_hours_per_week' => 40,
                // DOLE-compounded OT premiums: ordinary 125%, rest/special 169% (1.30×1.30),
                // regular holiday 260% (2.00×1.30).
                'weekday_overtime_rate' => 1.25,
                'weekend_overtime_rate' => 1.69,
                'holiday_overtime_rate' => 2.60,
                'grace_period_minutes' => 5,
                'late_threshold_minutes' => 15,
                'is_active' => true,
            ]
        );
    }

    /**
     * Initialize leave balances for an employee
     */
    private function initializeLeaveBalances(Employee $employee, array $leaveTypes): void
    {
        $currentYear = now()->year;

        foreach ($leaveTypes as $leaveType) {
            LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $currentYear,
                ],
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $currentYear,
                    'total_days' => $leaveType->days_per_year,
                    'used_days' => 0,
                    'remaining_days' => $leaveType->days_per_year,
                    'carried_over_days' => 0,
                ]
            );
        }
    }

    /**
     * Assign the default shift template to an employee
     */
    private function assignShiftToEmployee(Employee $employee, ?ShiftTemplate $shift): void
    {
        if ($shift && ! $employee->shift_template_id) {
            $employee->update(['shift_template_id' => $shift->id]);
        }
    }

    /**
     * Generate full-year EmployeeSchedule rows from an employee's assigned shift
     */
    private function generateYearSchedules(Employee $employee, ?ShiftTemplate $shift): void
    {
        if (! $shift) {
            return;
        }

        $workDays = $shift->work_days ?? [1, 2, 3, 4, 5];
        $startTime = $shift->start_time?->format('H:i:s') ?? '08:00:00';
        $endTime = $shift->end_time?->format('H:i:s') ?? '17:00:00';

        $yearStart = Carbon::now()->startOfYear();
        $yearEnd = Carbon::now()->endOfYear();

        $current = $yearStart->copy();

        while ($current <= $yearEnd) {
            $dayOfWeek = (int) $current->format('N'); // 1=Mon, 7=Sun

            if (in_array($dayOfWeek, $workDays)) {
                EmployeeSchedule::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $current->toDateString(),
                    ],
                    [
                        'employee_id' => $employee->id,
                        'shift_template_id' => $shift->id,
                        'date' => $current->toDateString(),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 'scheduled',
                    ]
                );
            }

            $current->addDay();
        }
    }
}
