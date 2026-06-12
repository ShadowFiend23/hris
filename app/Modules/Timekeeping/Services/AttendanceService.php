<?php

namespace App\Modules\Timekeeping\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\WorkPolicy;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    /**
     * Clock in an employee
     */
    public function clockIn(Employee $employee, ?Carbon $time = null): AttendanceRecord
    {
        $time = $time ?? now();
        $today = $time->toDateString();

        // Check if already clocked in today
        $existing = AttendanceRecord::forEmployee($employee->id)
            ->forDate($today)
            ->first();

        if ($existing && $existing->clock_in) {
            throw new \Exception('Already clocked in for today.');
        }

        $record = AttendanceRecord::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $today,
            ],
            [
                'company_id' => $employee->company_id,
                'clock_in' => $time,
                'status' => $this->determineStatus($employee, $time),
            ]
        );

        Log::info('Employee clocked in', [
            'employee_id' => $employee->id,
            'clock_in' => $time,
        ]);

        return $record->fresh(['employee', 'company']);
    }

    /**
     * Clock out an employee
     */
    public function clockOut(Employee $employee, ?Carbon $time = null): AttendanceRecord
    {
        $time = $time ?? now();
        $today = $time->toDateString();

        $record = AttendanceRecord::forEmployee($employee->id)
            ->forDate($today)
            ->first();

        if (! $record || ! $record->clock_in) {
            throw new \Exception('Must clock in before clocking out.');
        }

        if ($record->clock_out) {
            throw new \Exception('Already clocked out for today.');
        }

        $clockIn = Carbon::parse($record->clock_in);
        $totalMinutes = $clockIn->diffInMinutes($time) - $record->break_duration;
        $totalHours = round($totalMinutes / 60, 2);

        $record->update([
            'clock_out' => $time,
            'total_hours' => max(0, $totalHours),
        ]);

        Log::info('Employee clocked out', [
            'employee_id' => $employee->id,
            'clock_out' => $time,
            'total_hours' => $totalHours,
        ]);

        return $record->fresh(['employee', 'company']);
    }

    /**
     * Record a break
     */
    public function recordBreak(AttendanceRecord $record, int $minutes): AttendanceRecord
    {
        $record->update([
            'break_duration' => $record->break_duration + $minutes,
        ]);

        // Recalculate total hours if already clocked out
        if ($record->clock_out) {
            $clockIn = Carbon::parse($record->clock_in);
            $clockOut = Carbon::parse($record->clock_out);
            $totalMinutes = $clockIn->diffInMinutes($clockOut) - $record->break_duration;
            $record->update(['total_hours' => max(0, round($totalMinutes / 60, 2))]);
        }

        return $record->fresh();
    }

    /**
     * Get today's attendance for an employee
     */
    public function getTodayAttendance(Employee $employee): ?AttendanceRecord
    {
        return AttendanceRecord::forEmployee($employee->id)
            ->forDate(now()->toDateString())
            ->with(['employee', 'company'])
            ->first();
    }

    /**
     * Get attendance history with filters
     */
    public function getAttendanceHistory(Employee $employee, array $filters = []): LengthAwarePaginator
    {
        $query = AttendanceRecord::forEmployee($employee->id)
            ->with(['employee', 'company', 'approver'])
            ->orderByDesc('date');

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->forDateRange($filters['start_date'], $filters['end_date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get attendance summary for a date range
     */
    public function getAttendanceSummary(Employee $employee, Carbon $startDate, Carbon $endDate): array
    {
        $records = AttendanceRecord::forEmployee($employee->id)
            ->forDateRange($startDate, $endDate)
            ->get();

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $workingDays = $this->countWorkingDays($startDate, $endDate);

        return [
            'total_days' => $totalDays,
            'working_days' => $workingDays,
            'present_days' => $records->where('status', 'present')->count(),
            'late_days' => $records->where('status', 'late')->count(),
            'absent_days' => $records->where('status', 'absent')->count(),
            'half_days' => $records->where('status', 'half_day')->count(),
            'total_hours' => $records->sum('total_hours'),
            'average_hours' => $records->count() > 0 ? round($records->avg('total_hours'), 2) : 0,
            'attendance_rate' => $workingDays > 0
                ? round(($records->whereIn('status', ['present', 'late'])->count() / $workingDays) * 100, 1)
                : 0,
        ];
    }

    /**
     * Mark an employee as absent
     */
    public function markAbsent(Employee $employee, Carbon $date, ?string $reason = null): AttendanceRecord
    {
        return AttendanceRecord::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $date->toDateString(),
            ],
            [
                'company_id' => $employee->company_id,
                'status' => 'absent',
                'notes' => $reason,
            ]
        );
    }

    /**
     * Update attendance status based on work policy
     */
    public function updateAttendanceStatus(AttendanceRecord $record): AttendanceRecord
    {
        $status = $this->determineStatusFromRecord($record);
        $record->update(['status' => $status]);

        return $record->fresh();
    }

    /**
     * Get all employees' attendance for a given date (defaults to today)
     */
    public function getCompanyAttendanceToday(int $companyId, ?string $date = null): Collection
    {
        return AttendanceRecord::forCompany($companyId)
            ->forDate($date ?? now()->toDateString())
            ->with(['employee.department', 'employee.position'])
            ->get();
    }

    /**
     * Determine attendance status based on clock-in time against the employee's
     * actual scheduled shift start (not a hardcoded time).
     */
    private function determineStatus(Employee $employee, Carbon $clockIn): string
    {
        $policy = WorkPolicy::forCompany($employee->company_id)->active()->first();

        if (! $policy) {
            return 'present';
        }

        $shift = $employee->shiftTemplate;
        $startTime = $shift?->start_time ? $shift->start_time->format('H:i:s') : '09:00:00';
        $scheduledStart = Carbon::parse($clockIn->toDateString().' '.$startTime);

        $minutesLate = (int) $scheduledStart->diffInMinutes($clockIn, false);

        return $minutesLate > 0 && $policy->isLate($minutesLate) ? 'late' : 'present';
    }

    /**
     * Determine status from an existing record
     */
    private function determineStatusFromRecord(AttendanceRecord $record): string
    {
        if (! $record->clock_in) {
            return 'absent';
        }

        if ($record->total_hours < 4) {
            return 'half_day';
        }

        return $record->status;
    }

    /**
     * Count working days between two dates (excluding weekends)
     */
    private function countWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if (! $current->isWeekend()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
