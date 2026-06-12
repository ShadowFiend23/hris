<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\EmployeeSchedule;
use App\Modules\Timekeeping\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Static Philippine public holidays indexed by MM-DD.
     *
     * @var array<string, string>
     */
    private const PH_HOLIDAYS = [
        '01-01' => "New Year's Day",
        '04-09' => 'Araw ng Kagitingan (Day of Valor)',
        '04-17' => 'Maundy Thursday',   // approximate — update yearly
        '04-18' => 'Good Friday',
        '05-01' => 'Labor Day',
        '06-12' => 'Independence Day',
        '08-25' => 'National Heroes Day',
        '11-01' => "All Saints' Day",
        '11-30' => 'Bonifacio Day',
        '12-25' => 'Christmas Day',
        '12-30' => 'Rizal Day',
        '12-31' => 'New Year\'s Eve',
    ];

    public function index(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $employee = Employee::where('user_id', $request->user()->id)->first();

        if (! $employee) {
            return response()->json(['events' => []]);
        }

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $events = [];

        // Schedules (teal)
        $schedules = EmployeeSchedule::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get(['date', 'start_time', 'end_time', 'status']);

        foreach ($schedules as $schedule) {
            $events[] = [
                'date' => Carbon::parse($schedule->date)->toDateString(),
                'type' => 'schedule',
                'label' => 'Work Schedule',
                'detail' => Carbon::parse($schedule->start_time)->format('g:i A').' – '.Carbon::parse($schedule->end_time)->format('g:i A'),
                'color' => 'teal',
            ];
        }

        // Attendance records (purple)
        $attendances = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get(['date', 'clock_in', 'clock_out', 'status', 'total_hours']);

        foreach ($attendances as $record) {
            $colorMap = [
                'present' => 'purple',
                'late' => 'amber',
                'absent' => 'red',
                'half_day' => 'orange',
                'on_leave' => 'blue',
            ];

            $events[] = [
                'date' => Carbon::parse($record->date)->toDateString(),
                'type' => 'attendance',
                'label' => ucfirst(str_replace('_', ' ', $record->status ?? 'present')),
                'detail' => $record->clock_in
                    ? Carbon::parse($record->clock_in)->format('g:i A').($record->clock_out ? ' / '.Carbon::parse($record->clock_out)->format('g:i A') : '')
                    : null,
                'color' => $colorMap[$record->status] ?? 'purple',
            ];
        }

        // Approved leaves (green)
        $leaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startOfMonth, $endOfMonth): void {
                $q->whereBetween('start_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orWhereBetween('end_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orWhere(function ($q) use ($startOfMonth, $endOfMonth): void {
                        $q->where('start_date', '<=', $startOfMonth->toDateString())
                            ->where('end_date', '>=', $endOfMonth->toDateString());
                    });
            })
            ->with('leaveType:id,name,code')
            ->get(['id', 'leave_type_id', 'start_date', 'end_date', 'total_days']);

        foreach ($leaves as $leave) {
            $start = Carbon::parse($leave->start_date)->max($startOfMonth);
            $end = Carbon::parse($leave->end_date)->min($endOfMonth);

            $current = $start->copy();
            while ($current <= $end) {
                $events[] = [
                    'date' => $current->toDateString(),
                    'type' => 'leave',
                    'label' => $leave->leaveType?->name ?? 'Leave',
                    'detail' => $leave->leaveType?->code,
                    'color' => 'green',
                ];
                $current->addDay();
            }
        }

        // Philippine public holidays (static)
        $current = $startOfMonth->copy();
        while ($current <= $endOfMonth) {
            $key = $current->format('m-d');
            if (isset(self::PH_HOLIDAYS[$key])) {
                $events[] = [
                    'date' => $current->toDateString(),
                    'type' => 'holiday',
                    'label' => self::PH_HOLIDAYS[$key],
                    'detail' => self::PH_HOLIDAYS[$key],
                    'color' => 'rose',
                ];
            }
            $current->addDay();
        }

        // Sort by date
        usort($events, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return response()->json(['events' => $events]);
    }
}
