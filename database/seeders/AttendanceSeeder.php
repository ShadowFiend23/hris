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
        'Regular Day Shift',
        'Morning Shift',
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
            ->orderBy('id')
            ->get();

        // ~35% of employees accrue a few random absences for realistic payroll data.
        $employeeIds = $employees->pluck('id')->toArray();
        $absenteeCount = (int) ceil(count($employeeIds) * 0.35);
        $absenteeIds = array_slice($employeeIds, 0, $absenteeCount);

        $rotationIndex = 0;
        $index = 0;

        foreach ($employees as $employee) {
            $shift = $shiftTemplates->firstWhere('id', $employee->shift_template_id);

            // Fallback only if the employee was never assigned a shift.
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

            $this->generateYearSchedules($employee, $shift);

            $maxAbsences = in_array($employee->id, $absenteeIds) ? 2 + ($employee->id % 3) : 0;
            $withHalfDay = $index % 5 === 2;       // ~1 in 5 gets a half day
            $withExplicitAbsent = $index % 5 === 4; // ~1 in 5 gets an explicit absent record

            $this->createAttendanceRecords($employee, $shift, $maxAbsences, $withHalfDay, $withExplicitAbsent);
            $index++;
        }
    }

    private function createAttendanceRecords(
        Employee $employee,
        ShiftTemplate $shift,
        int $maxAbsences,
        bool $withHalfDay,
        bool $withExplicitAbsent
    ): void {
        $startHour = (int) $shift->start_time->format('H');
        $startMinute = (int) $shift->start_time->format('i');
        $endHour = (int) $shift->end_time->format('H');
        $endMinute = (int) $shift->end_time->format('i');

        // Night Shift (e.g. 22:00–06:00) — clock_out lands on the next calendar day.
        $crossesMidnight = $startHour > $endHour;

        $workDays = $shift->work_days ?? [1, 2, 3, 4, 5];
        $breakDuration = $shift->break_duration ?? 60;

        // Deterministic absence work-day indices (idempotent across re-runs).
        $absenceIndices = array_slice([7, 17, 27, 37], 0, $maxAbsences);

        $date = Carbon::now()->subDays(self::DAYS_TO_SEED)->startOfDay();
        $today = Carbon::now()->startOfDay();
        $workDayIndex = 0;

        while ($date <= $today) {
            $dayOfWeek = (int) $date->format('N'); // 1=Mon … 7=Sun

            if (in_array($dayOfWeek, $workDays)) {
                // Deterministic half-day on the 2nd work day.
                if ($withHalfDay && $workDayIndex === 1) {
                    $this->createHalfDay($employee, $date, $startHour, $startMinute, $breakDuration);
                    $workDayIndex++;
                    $date->addDay();

                    continue;
                }

                // Deterministic explicit absence on the 3rd work day.
                if ($withExplicitAbsent && $workDayIndex === 2) {
                    $this->createAbsent($employee, $date);
                    $workDayIndex++;
                    $date->addDay();

                    continue;
                }

                // Deterministic no-record absences for absentee employees.
                if (in_array($workDayIndex, $absenceIndices, true)) {
                    $workDayIndex++;
                    $date->addDay();

                    continue;
                }

                $clockInVariance = rand(-5, 30);
                $clockIn = $date->copy()->setTime($startHour, $startMinute, 0)->addMinutes($clockInVariance);

                $clockOutBase = $crossesMidnight
                    ? $date->copy()->addDay()->setTime($endHour, $endMinute, 0)
                    : $date->copy()->setTime($endHour, $endMinute, 0);
                $clockOut = $clockOutBase->addMinutes(rand(-15, 45));

                $totalMinutes = $clockIn->diffInMinutes($clockOut) - $breakDuration;
                $totalHours = round(max($totalMinutes, 0) / 60, 2);

                $scheduledStart = $date->copy()->setTime($startHour, $startMinute, 0);
                $status = $clockIn->gt($scheduledStart->copy()->addMinutes(15)) ? 'late' : 'present';

                AttendanceRecord::firstOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->copy()],
                    [
                        'company_id' => $employee->company_id,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'total_hours' => $totalHours,
                        'break_duration' => $breakDuration,
                        'status' => $status,
                        'source' => 'manual',
                    ]
                );

                $workDayIndex++;
            }

            $date->addDay();
        }
    }

    private function createHalfDay(Employee $employee, Carbon $date, int $startHour, int $startMinute, int $breakDuration): void
    {
        $clockIn = $date->copy()->setTime($startHour, $startMinute, 0);
        $clockOut = $clockIn->copy()->addHours(4);
        $totalHours = round(max((4 * 60) - $breakDuration, 0) / 60, 2);

        AttendanceRecord::firstOrCreate(
            ['employee_id' => $employee->id, 'date' => $date->copy()],
            [
                'company_id' => $employee->company_id,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'total_hours' => $totalHours,
                'break_duration' => $breakDuration,
                'status' => 'half_day',
                'source' => 'manual',
            ]
        );
    }

    private function createAbsent(Employee $employee, Carbon $date): void
    {
        AttendanceRecord::firstOrCreate(
            ['employee_id' => $employee->id, 'date' => $date->copy()],
            [
                'company_id' => $employee->company_id,
                'clock_in' => null,
                'clock_out' => null,
                'total_hours' => 0,
                'break_duration' => 0,
                'status' => 'absent',
                'source' => 'manual',
            ]
        );
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
                $exists = EmployeeSchedule::where('employee_id', $employee->id)
                    ->whereDate('date', $current->toDateString())
                    ->exists();

                if (! $exists) {
                    EmployeeSchedule::create([
                        'employee_id' => $employee->id,
                        'date' => $current->toDateString(),
                        'shift_template_id' => $shift->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 'scheduled',
                    ]);
                }
            }

            $current->addDay();
        }
    }
}
