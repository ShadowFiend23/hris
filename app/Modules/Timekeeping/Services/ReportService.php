<?php

namespace App\Modules\Timekeeping\Services;

use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Generate attendance report
     */
    public function generateAttendanceReport(int $companyId, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $query = AttendanceRecord::forCompany($companyId)
            ->forDateRange($startDate, $endDate)
            ->with(['employee.department', 'employee.position']);

        if (! empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        $records = $query->orderBy('date')->orderBy('employee_id')->get();

        // Group by employee
        $groupedByEmployee = $records->groupBy('employee_id');

        $reportData = [];
        foreach ($groupedByEmployee as $employeeId => $employeeRecords) {
            $employee = $employeeRecords->first()->employee;
            $reportData[] = [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->full_name ?? $employee->first_name.' '.$employee->last_name,
                    'employee_id' => $employee->employee_id,
                    'department' => $employee->department?->name,
                    'position' => $employee->position?->position_name,
                ],
                'summary' => [
                    'total_days' => $employeeRecords->count(),
                    'present_days' => $employeeRecords->where('status', 'present')->count(),
                    'late_days' => $employeeRecords->where('status', 'late')->count(),
                    'absent_days' => $employeeRecords->where('status', 'absent')->count(),
                    'half_days' => $employeeRecords->where('status', 'half_day')->count(),
                    'total_hours' => $employeeRecords->sum('total_hours'),
                    'average_hours' => round($employeeRecords->avg('total_hours'), 2),
                ],
                'records' => $employeeRecords->map(function ($record) {
                    return [
                        'date' => $record->date->toDateString(),
                        'clock_in' => $record->clock_in?->format('H:i:s'),
                        'clock_out' => $record->clock_out?->format('H:i:s'),
                        'total_hours' => $record->total_hours,
                        'status' => $record->status,
                    ];
                })->values(),
            ];
        }

        return [
            'report_type' => 'attendance',
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toDateTimeString(),
            'summary' => [
                'total_employees' => count($reportData),
                'total_records' => $records->count(),
                'overall_attendance_rate' => $this->calculateOverallAttendanceRate($records),
            ],
            'data' => $reportData,
        ];
    }

    /**
     * Generate leave report
     */
    public function generateLeaveReport(int $companyId, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $query = LeaveRequest::whereHas('employee', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($inner) use ($startDate, $endDate) {
                        $inner->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->with(['employee.department', 'leaveType', 'approver']);

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        $requests = $query->orderBy('start_date')->get();

        // Group by leave type
        $byLeaveType = $requests->groupBy('leave_type_id');

        $leaveTypeSummary = [];
        foreach ($byLeaveType as $leaveTypeId => $typeRequests) {
            $leaveType = $typeRequests->first()->leaveType;
            $leaveTypeSummary[] = [
                'leave_type' => $leaveType->name,
                'total_requests' => $typeRequests->count(),
                'total_days' => $typeRequests->where('status', 'approved')->sum('total_days'),
                'pending' => $typeRequests->where('status', 'pending')->count(),
                'approved' => $typeRequests->where('status', 'approved')->count(),
                'rejected' => $typeRequests->where('status', 'rejected')->count(),
            ];
        }

        return [
            'report_type' => 'leave',
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toDateTimeString(),
            'summary' => [
                'total_requests' => $requests->count(),
                'total_days_taken' => $requests->where('status', 'approved')->sum('total_days'),
                'pending_requests' => $requests->where('status', 'pending')->count(),
                'approved_requests' => $requests->where('status', 'approved')->count(),
                'rejected_requests' => $requests->where('status', 'rejected')->count(),
            ],
            'by_leave_type' => $leaveTypeSummary,
            'data' => $requests->map(function ($request) {
                return [
                    'employee' => [
                        'id' => $request->employee->id,
                        'name' => $request->employee->first_name.' '.$request->employee->last_name,
                        'department' => $request->employee->department?->name,
                    ],
                    'leave_type' => $request->leaveType->name,
                    'start_date' => $request->start_date->toDateString(),
                    'end_date' => $request->end_date->toDateString(),
                    'total_days' => $request->total_days,
                    'status' => $request->status,
                    'reason' => $request->reason,
                    'approved_by' => $request->approver?->name,
                    'approved_at' => $request->approved_at?->toDateTimeString(),
                ];
            })->values(),
        ];
    }

    /**
     * Generate overtime report
     */
    public function generateOvertimeReport(int $companyId, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $query = OvertimeRecord::forCompany($companyId)
            ->forDateRange($startDate, $endDate)
            ->with(['employee.department', 'approver']);

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['overtime_type'])) {
            $query->where('overtime_type', $filters['overtime_type']);
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        $records = $query->orderBy('date')->get();

        // Group by employee
        $groupedByEmployee = $records->groupBy('employee_id');

        $reportData = [];
        foreach ($groupedByEmployee as $employeeId => $employeeRecords) {
            $employee = $employeeRecords->first()->employee;
            $approvedRecords = $employeeRecords->whereIn('status', ['approved', 'paid']);

            $reportData[] = [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->first_name.' '.$employee->last_name,
                    'employee_id' => $employee->employee_id,
                    'department' => $employee->department?->name,
                ],
                'summary' => [
                    'total_requests' => $employeeRecords->count(),
                    'approved_hours' => $approvedRecords->sum('hours'),
                    'pending_hours' => $employeeRecords->where('status', 'pending')->sum('hours'),
                    'by_type' => [
                        'weekday' => $approvedRecords->where('overtime_type', 'weekday')->sum('hours'),
                        'weekend' => $approvedRecords->where('overtime_type', 'weekend')->sum('hours'),
                        'holiday' => $approvedRecords->where('overtime_type', 'holiday')->sum('hours'),
                    ],
                ],
                'records' => $employeeRecords->map(function ($record) {
                    return [
                        'date' => $record->date->toDateString(),
                        'start_at' => $record->start_at?->toDateTimeString(),
                        'end_at' => $record->end_at?->toDateTimeString(),
                        'hours' => $record->hours,
                        'overtime_type' => $record->overtime_type,
                        'status' => $record->status,
                        'reason' => $record->reason,
                    ];
                })->values(),
            ];
        }

        return [
            'report_type' => 'overtime',
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toDateTimeString(),
            'summary' => [
                'total_employees' => count($reportData),
                'total_requests' => $records->count(),
                'total_hours' => $records->whereIn('status', ['approved', 'paid'])->sum('hours'),
                'by_type' => [
                    'weekday' => $records->where('overtime_type', 'weekday')->whereIn('status', ['approved', 'paid'])->sum('hours'),
                    'weekend' => $records->where('overtime_type', 'weekend')->whereIn('status', ['approved', 'paid'])->sum('hours'),
                    'holiday' => $records->where('overtime_type', 'holiday')->whereIn('status', ['approved', 'paid'])->sum('hours'),
                ],
            ],
            'data' => $reportData,
        ];
    }

    /**
     * Generate summary report for an employee
     */
    public function generateSummaryReport($employee, Carbon $startDate, Carbon $endDate): array
    {
        $attendance = AttendanceRecord::forEmployee($employee->id)
            ->forDateRange($startDate, $endDate)
            ->get();

        $leaveRequests = LeaveRequest::forEmployee($employee->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->with('leaveType')
            ->get();

        $overtime = OvertimeRecord::forEmployee($employee->id)
            ->forDateRange($startDate, $endDate)
            ->get();

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->first_name.' '.$employee->last_name,
                'employee_id' => $employee->employee_id,
                'department' => $employee->department?->name,
                'position' => $employee->position?->position_name,
            ],
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'attendance' => [
                'total_days' => $attendance->count(),
                'present' => $attendance->where('status', 'present')->count(),
                'late' => $attendance->where('status', 'late')->count(),
                'absent' => $attendance->where('status', 'absent')->count(),
                'half_day' => $attendance->where('status', 'half_day')->count(),
                'total_hours' => $attendance->sum('total_hours'),
                'average_hours' => round($attendance->avg('total_hours') ?? 0, 2),
            ],
            'leave' => [
                'total_requests' => $leaveRequests->count(),
                'approved_days' => $leaveRequests->where('status', 'approved')->sum('total_days'),
                'pending_requests' => $leaveRequests->where('status', 'pending')->count(),
            ],
            'overtime' => [
                'total_requests' => $overtime->count(),
                'approved_hours' => $overtime->whereIn('status', ['approved', 'paid'])->sum('hours'),
                'pending_hours' => $overtime->where('status', 'pending')->sum('hours'),
            ],
        ];
    }

    /**
     * Calculate overall attendance rate
     */
    private function calculateOverallAttendanceRate(Collection $records): float
    {
        if ($records->isEmpty()) {
            return 0;
        }

        $presentOrLate = $records->whereIn('status', ['present', 'late'])->count();

        return round(($presentOrLate / $records->count()) * 100, 1);
    }
}
