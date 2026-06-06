<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * Get current user's attendance for today
     */
    public function today(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $attendance = $this->attendanceService->getTodayAttendance($employee);

        return response()->json([
            'data' => $attendance,
            'is_clocked_in' => $attendance?->isClockedIn ?? false,
        ]);
    }

    /**
     * Clock in
     */
    public function clockIn(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        try {
            $attendance = $this->attendanceService->clockIn($employee);

            return response()->json([
                'message' => 'Clocked in successfully',
                'data' => $attendance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Clock out
     */
    public function clockOut(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        try {
            $attendance = $this->attendanceService->clockOut($employee);

            return response()->json([
                'message' => 'Clocked out successfully',
                'data' => $attendance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Record a break
     */
    public function recordBreak(Request $request): JsonResponse
    {
        $request->validate([
            'minutes' => 'required|integer|min:1|max:120',
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $attendance = $this->attendanceService->getTodayAttendance($employee);

        if (! $attendance) {
            return response()->json(['error' => 'Must clock in first'], 400);
        }

        $attendance = $this->attendanceService->recordBreak($attendance, $request->minutes);

        return response()->json([
            'message' => 'Break recorded',
            'data' => $attendance,
        ]);
    }

    /**
     * Get attendance history
     */
    public function history(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        if ($request->filled('employee_id')) {
            $target = Employee::find($request->employee_id);
            if (! $target || (int) $target->company_id !== (int) $employee->company_id) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }
            $employee = $target;
        }

        $filters = $request->only(['start_date', 'end_date', 'status', 'per_page']);
        $history = $this->attendanceService->getAttendanceHistory($employee, $filters);

        return response()->json($history);
    }

    /**
     * Get attendance summary
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        if ($request->filled('employee_id')) {
            $target = Employee::find($request->employee_id);
            if (! $target || (int) $target->company_id !== (int) $employee->company_id) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }
            $employee = $target;
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $summary = $this->attendanceService->getAttendanceSummary($employee, $startDate, $endDate);

        return response()->json(['data' => $summary]);
    }

    /**
     * Get company-wide attendance for today (admin)
     */
    public function companyToday(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $date = $request->filled('date') ? $request->input('date') : now()->toDateString();

        $attendance = $this->attendanceService->getCompanyAttendanceToday($companyId, $date);

        $data = $attendance->map(fn ($record) => [
            'employee_id' => $record->employee_id,
            'employee_code' => $record->employee?->employee_id ?? '—',
            'name' => trim(($record->employee?->first_name ?? '').' '.($record->employee?->last_name ?? '')),
            'department' => $record->employee?->department?->name,
            'position' => $record->employee?->position?->position_name,
            'attendance_record_id' => $record->id,
            'clock_in' => $record->clock_in,
            'clock_out' => $record->clock_out,
            'total_hours' => $record->total_hours,
            'status' => $record->status,
            'adjusted_by' => $record->adjusted_by,
            'adjusted_at' => $record->adjusted_at,
            'adjustment_reason' => $record->adjustment_reason,
        ])->values();

        return response()->json([
            'data' => $data,
            'date' => $date,
        ]);
    }

    /**
     * Get attendance for a specific employee (admin)
     */
    public function employeeAttendance(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $employee);

        $filters = $request->only(['start_date', 'end_date', 'status', 'per_page']);
        $history = $this->attendanceService->getAttendanceHistory($employee, $filters);

        return response()->json($history);
    }

    /**
     * Mark employee as absent (admin)
     */
    public function markAbsent(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $employee);

        $request->validate([
            'date' => 'required|date',
            'reason' => 'nullable|string|max:500',
        ]);

        $attendance = $this->attendanceService->markAbsent(
            $employee,
            Carbon::parse($request->date),
            $request->reason
        );

        return response()->json([
            'message' => 'Employee marked as absent',
            'data' => $attendance,
        ]);
    }

    /**
     * Update attendance record (admin)
     */
    public function update(Request $request, AttendanceRecord $record): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $record->employee);

        $request->validate([
            'clock_in' => 'nullable|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s',
            'status' => 'nullable|in:present,late,absent,half_day,on_leave',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data = $request->only(['clock_in', 'clock_out', 'status', 'notes']);

        if (isset($data['clock_in'])) {
            $data['clock_in'] = Carbon::parse($record->date->toDateString().' '.$data['clock_in']);
        }

        if (isset($data['clock_out'])) {
            $data['clock_out'] = Carbon::parse($record->date->toDateString().' '.$data['clock_out']);
        }

        // Recalculate total hours if times changed
        if (isset($data['clock_in']) || isset($data['clock_out'])) {
            $clockIn = $data['clock_in'] ?? $record->clock_in;
            $clockOut = $data['clock_out'] ?? $record->clock_out;

            if ($clockIn && $clockOut) {
                $totalMinutes = Carbon::parse($clockIn)->diffInMinutes(Carbon::parse($clockOut)) - $record->break_duration;
                $data['total_hours'] = max(0, round($totalMinutes / 60, 2));
            }
        }

        $data['approved_by'] = $request->user()->id;

        $record->update($data);

        return response()->json([
            'message' => 'Attendance record updated',
            'data' => $record->fresh(['employee', 'approver']),
        ]);
    }

    /**
     * Authorize company access
     */
    private function authorizeCompanyAccess(Request $request, Employee $employee): void
    {
        $userCompanyId = $request->user()->employee?->company_id;

        if ((int) $userCompanyId !== (int) $employee->company_id) {
            abort(403, 'Unauthorized access to this employee');
        }
    }
}
