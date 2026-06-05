<?php

namespace App\Modules\Timekeeping\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use Carbon\Carbon;

class DtrService
{
    /**
     * Generate DTR data for a given employee and month.
     *
     * @return array{
     *     employee: Employee,
     *     year: int,
     *     month: int,
     *     month_name: string,
     *     official_am: string,
     *     official_pm: string,
     *     rows: array<int, array{
     *         day: int,
     *         morning_arrival: string,
     *         morning_departure: string,
     *         afternoon_arrival: string,
     *         afternoon_departure: string,
     *         undertime_hours: int,
     *         undertime_minutes: int,
     *     }>,
     *     total_undertime_hours: int,
     *     total_undertime_minutes: int,
     * }
     */
    public function generateDtrData(Employee $employee, int $year, int $month): array
    {
        $employee->loadMissing(['shiftTemplate', 'position', 'department']);

        $shiftTemplate = $employee->shiftTemplate;

        // Official times for header display
        $officialStart = $shiftTemplate ? substr((string) $shiftTemplate->start_time, 0, 5) : '08:00';
        $officialBreakStart = $shiftTemplate?->break_start_time ? substr($shiftTemplate->break_start_time, 0, 5) : '12:00';
        $officialBreakEnd = $shiftTemplate?->break_end_time ? substr($shiftTemplate->break_end_time, 0, 5) : '13:00';
        $officialEnd = $shiftTemplate ? substr((string) $shiftTemplate->end_time, 0, 5) : '17:00';

        $officialAm = $officialStart.' - '.$officialBreakStart;
        $officialPm = $officialBreakEnd.' - '.$officialEnd;

        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        // Load attendance records for the month
        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->keyBy(fn ($r) => (int) $r->date->format('j'));

        $rows = [];
        $totalUndertimeMinutes = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $record = $records->get($day);
            $date = Carbon::create($year, $month, $day);

            $morningArrival = '';
            $morningDeparture = '';
            $afternoonArrival = '';
            $afternoonDeparture = '';
            $undertimeMinutes = 0;

            if ($record && $record->clock_in) {
                $morningArrival = $record->clock_in->format('H:i');
            }

            if ($record && $record->morning_out) {
                $morningDeparture = $record->morning_out->format('H:i');
            } elseif ($record && $record->clock_out && ! ($shiftTemplate && $shiftTemplate->hasSplitShift())) {
                // No split shift: clock_out is the afternoon departure
                $afternoonDeparture = $record->clock_out->format('H:i');
            }

            if ($record && $record->afternoon_in) {
                $afternoonArrival = $record->afternoon_in->format('H:i');
            }

            if ($record && $record->clock_out && $shiftTemplate && $shiftTemplate->hasSplitShift()) {
                $afternoonDeparture = $record->clock_out->format('H:i');
            }

            // Calculate undertime (only on working days with a record)
            if ($record && $shiftTemplate && $record->clock_in) {
                $undertimeMinutes = $this->calculateUndertimeMinutes(
                    $record,
                    $shiftTemplate->start_time,
                    $shiftTemplate->end_time,
                    $shiftTemplate->hasSplitShift() ? $shiftTemplate->break_start_time : null,
                    $shiftTemplate->hasSplitShift() ? $shiftTemplate->break_end_time : null,
                    $date
                );
            }

            $totalUndertimeMinutes += $undertimeMinutes;

            $rows[] = [
                'day' => $day,
                'morning_arrival' => $morningArrival,
                'morning_departure' => $morningDeparture,
                'afternoon_arrival' => $afternoonArrival,
                'afternoon_departure' => $afternoonDeparture,
                'undertime_hours' => (int) floor($undertimeMinutes / 60),
                'undertime_minutes' => $undertimeMinutes % 60,
            ];
        }

        return [
            'employee' => $employee,
            'year' => $year,
            'month' => $month,
            'month_name' => Carbon::create($year, $month)->format('F Y'),
            'official_am' => $officialAm,
            'official_pm' => $officialPm,
            'rows' => $rows,
            'total_undertime_hours' => (int) floor($totalUndertimeMinutes / 60),
            'total_undertime_minutes' => $totalUndertimeMinutes % 60,
        ];
    }

    /**
     * Calculate undertime minutes for a given attendance record.
     * Undertime = late arrival minutes + early departure minutes.
     */
    private function calculateUndertimeMinutes(
        AttendanceRecord $record,
        string $shiftStart,
        string $shiftEnd,
        ?string $breakStart,
        ?string $breakEnd,
        Carbon $date
    ): int {
        $undertimeMinutes = 0;

        $officialStart = Carbon::parse($date->toDateString().' '.substr($shiftStart, -8));
        $officialEnd = Carbon::parse($date->toDateString().' '.substr($shiftEnd, -8));

        // Late morning arrival
        if ($record->clock_in && $record->clock_in->gt($officialStart)) {
            $undertimeMinutes += $record->clock_in->diffInMinutes($officialStart);
        }

        // Early afternoon departure (clock_out before official end)
        if ($record->clock_out && $record->clock_out->lt($officialEnd)) {
            $undertimeMinutes += $officialEnd->diffInMinutes($record->clock_out);
        }

        // For split shift: early morning departure (before break_start) and late afternoon arrival (after break_end)
        if ($breakStart && $breakEnd) {
            $officialBreakStart = Carbon::parse($date->toDateString().' '.$breakStart);
            $officialBreakEnd = Carbon::parse($date->toDateString().' '.$breakEnd);

            if ($record->morning_out && $record->morning_out->lt($officialBreakStart)) {
                $undertimeMinutes += $officialBreakStart->diffInMinutes($record->morning_out);
            }

            if ($record->afternoon_in && $record->afternoon_in->gt($officialBreakEnd)) {
                $undertimeMinutes += $record->afternoon_in->diffInMinutes($officialBreakEnd);
            }
        }

        return $undertimeMinutes;
    }
}
