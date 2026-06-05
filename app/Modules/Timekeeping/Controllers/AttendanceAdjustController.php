<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timekeeping\AdjustAttendanceRequest;
use App\Modules\Core\Models\AuditLogs;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AttendanceAdjustController extends Controller
{
    /**
     * Return today's attendance records for the authenticated manager's direct reports.
     */
    public function team(): JsonResponse
    {
        $user = Auth::user();
        $managerEmployee = Employee::where('user_id', $user->id)->first();

        if (! $managerEmployee) {
            return response()->json(['data' => []]);
        }

        $today = now()->toDateString();

        $subordinates = Employee::where('supervisor_id', $managerEmployee->id)
            ->where('is_active', true)
            ->with([
                'position:id,position_name',
                'department:id,name',
                'attendanceRecords' => fn ($q) => $q->whereDate('date', $today),
            ])
            ->get(['id', 'employee_id', 'first_name', 'last_name', 'department_id', 'position_id']);

        $data = $subordinates->map(function (Employee $emp) {
            $record = $emp->attendanceRecords->first();

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_id,
                'name' => $emp->full_name,
                'department' => $emp->department?->name,
                'position' => $emp->position?->position_name,
                'attendance_record_id' => $record?->id,
                'clock_in' => $record?->clock_in,
                'clock_out' => $record?->clock_out,
                'total_hours' => $record?->total_hours,
                'status' => $record?->status ?? 'no_record',
                'adjusted_by' => $record?->adjusted_by,
                'adjusted_at' => $record?->adjusted_at,
                'adjustment_reason' => $record?->adjustment_reason,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Manually adjust a direct report's attendance record.
     */
    public function adjust(AdjustAttendanceRequest $request, AttendanceRecord $record): JsonResponse
    {
        $user = Auth::user();
        $managerEmployee = Employee::where('user_id', $user->id)->first();

        if (! $managerEmployee) {
            abort(403, 'No employee record linked to your account.');
        }

        // Gate: can only adjust direct reports
        $targetEmployee = Employee::find($record->employee_id);
        if (! $targetEmployee || (int) $targetEmployee->supervisor_id !== (int) $managerEmployee->id) {
            abort(403, 'You can only adjust attendance for your direct reports.');
        }

        $before = [
            'clock_in' => $record->clock_in,
            'clock_out' => $record->clock_out,
            'total_hours' => $record->total_hours,
        ];

        $clockIn = Carbon::parse($request->clock_in);
        $clockOut = $request->clock_out ? Carbon::parse($request->clock_out) : null;
        $totalHours = $clockOut ? round($clockOut->diffInMinutes($clockIn) / 60, 2) : $record->total_hours;

        $record->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'total_hours' => $totalHours,
            'adjusted_by' => $user->id,
            'adjusted_at' => now(),
            'adjustment_reason' => $request->adjustment_reason,
        ]);

        AuditLogs::create([
            'user_id' => $user->id,
            'action' => 'attendance.adjusted',
            'subject_type' => AttendanceRecord::class,
            'subject_id' => $record->id,
            'changes' => [
                'before' => $before,
                'after' => [
                    'clock_in' => $record->clock_in,
                    'clock_out' => $record->clock_out,
                    'total_hours' => $record->total_hours,
                ],
                'reason' => $request->adjustment_reason,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Attendance adjusted successfully.',
            'data' => $record->fresh(),
        ]);
    }
}
