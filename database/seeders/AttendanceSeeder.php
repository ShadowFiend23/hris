<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\EmployeeSchedule;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    private const SHIFT_ROTATION = [
        'Morning Shift',
        'Regular Day Shift',
        'Afternoon Shift',
        'Night Shift',
        'Flexible Hours',
    ];

    private const DAYS_TO_SEED = 90;

    public function run(): void
    {
        $company = Company::where('slug', 'test-company')->firstOrFail();

        $shiftTemplates = ShiftTemplate::where('company_id', $company->id)
            ->active()
            ->get();

        $employees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        $rotationIndex = 0;

        // Assign a random number of absences to ~35% of employees for realistic payroll data
        $employeeIds = $employees->pluck('id')->toArray();
        $absenteeCount = (int) ceil(count($employeeIds) * 0.35);
        $absenteeIds = array_slice($employeeIds, 0, $absenteeCount);
        shuffle($absenteeIds);

        foreach ($employees as $employee) {
            $hadExistingShift = $employee->shift_template_id !== null;
            $shift = $shiftTemplates->first(fn ($s) => $s->id === $employee->shift_template_id);

            if (! $shift) {
                $shiftName = self::SHIFT_ROTATION[$rotationIndex % count(self::SHIFT_ROTATION)];
                $shift = $shiftTemplates->firstWhere('name', $shiftName);

                if ($shift) {
                    $employee->update(['shift_template_id' => $shift->id]);
                }

                $rotationIndex++;
            }

            if (! $shift) {
                continue;
            }

            // Only generate year schedules for employees that didn't already have one;
            // existing employees (e.g. EMP-001, EMP-002) are covered by TimekeepingSeeder.
            if (! $hadExistingShift) {
                $this->generateYearSchedules($employee, $shift);
            }

            $maxAbsences = in_array($employee->id, $absenteeIds) ? rand(2, 4) : 0;
            $this->createAttendanceRecords($employee, $shift, $maxAbsences);
        }
    }

    private function createAttendanceRecords(Employee $employee, ShiftTemplate $shift, int $maxAbsences = 0): void
    {
        $startHour = (int) $shift->start_time->format('H');
        $startMinute = (int) $shift->start_time->format('i');
        $endHour = (int) $shift->end_time->format('H');
        $endMinute = (int) $shift->end_time->format('i');

        // Night Shift (e.g. 22:00–06:00) — clock_out lands on the next calendar day
        $crossesMidnight = $startHour > $endHour;

        $workDays = $shift->work_days ?? [1, 2, 3, 4, 5];
        $breakDuration = $shift->break_duration ?? 60;

        $date = Carbon::now()->subDays(self::DAYS_TO_SEED)->startOfDay();
        $today = Carbon::now()->startOfDay();
        $absencesCreated = 0;

        while ($date <= $today) {
            $dayOfWeek = (int) $date->format('N'); // 1=Mon … 7=Sun

            if (in_array($dayOfWeek, $workDays)) {
                // Randomly skip work days to simulate absences, spread across the seeded window
                if ($absencesCreated < $maxAbsences && rand(1, self::DAYS_TO_SEED) <= $maxAbsences * 3) {
                    $absencesCreated++;
                    $date->addDay();

                    continue;
                }

                $clockInVariance = rand(-5, 30);

                $clockIn = $date->copy()
                    ->setTime($startHour, $startMinute, 0)
                    ->addMinutes($clockInVariance);

                $clockOutBase = $crossesMidnight
                    ? $date->copy()->addDay()->setTime($endHour, $endMinute, 0)
                    : $date->copy()->setTime($endHour, $endMinute, 0);

                $clockOut = $clockOutBase->addMinutes(rand(-15, 45));

                $totalMinutes = $clockIn->diffInMinutes($clockOut) - $breakDuration;
                $totalHours = round(max($totalMinutes, 0) / 60, 2);

                $scheduledStart = $date->copy()->setTime($startHour, $startMinute, 0);
                $status = $clockIn->gt($scheduledStart->copy()->addMinutes(15)) ? 'late' : 'present';

                // Pass a Carbon instance (not a date string) so the PDO binding formats
                // as 'Y-m-d H:i:s', matching how Eloquent's date cast serializes for storage.
                AttendanceRecord::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $date->copy(),
                    ],
                    [
                        'employee_id' => $employee->id,
                        'company_id' => $employee->company_id,
                        'date' => $date->copy(),
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'total_hours' => $totalHours,
                        'break_duration' => $breakDuration,
                        'status' => $status,
                        'source' => 'manual',
                    ]
                );
            }

            $date->addDay();
        }
    }

    private function generateYearSchedules(Employee $employee, ShiftTemplate $shift): void
    {
        $workDays = $shift->work_days ?? [1, 2, 3, 4, 5];
        $startTime = $shift->start_time->format('H:i:s');
        $endTime = $shift->end_time->format('H:i:s');

        $current = Carbon::now()->startOfYear();
        $yearEnd = Carbon::now()->endOfYear();

        while ($current <= $yearEnd) {
            $dayOfWeek = (int) $current->format('N');

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
