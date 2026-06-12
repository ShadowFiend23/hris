<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\PayrollItem;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\LeaveBalance;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return $this->adminDashboard($user, $request);
        }

        if ($user->hasRole('manager')) {
            return $this->managerDashboard($user);
        }

        return $this->employeeDashboard($user);
    }

    private function adminDashboard(mixed $user, Request $request): Response
    {
        $companyId = $user->company_id;

        $range = (int) $request->query('range', 30);
        $range = in_array($range, [7, 30, 90], true) ? $range : 30;

        $today = now();
        $rangeStart = $today->copy()->subDays($range - 1);

        $totalEmployees = Employee::where('company_id', $companyId)
            ->where('employment_status', 'active')
            ->count();

        $totalDepartments = Department::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $presentToday = AttendanceRecord::where('company_id', $companyId)
            ->whereDate('date', $today->toDateString())
            ->whereIn('status', ['present', 'late'])
            ->count();
        $attendanceTodayPct = $totalEmployees > 0
            ? round(($presentToday / $totalEmployees) * 100, 1)
            : 0;

        $pendingLeaves = LeaveRequest::whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'pending')
            ->count();

        $pendingOvertime = OvertimeRecord::where('company_id', $companyId)
            ->where('status', 'pending')
            ->count();

        // Turnover rate = resignations in the last 6 months over current active headcount.
        $departures6m = Employee::where('company_id', $companyId)
            ->whereNotNull('date_resigned')
            ->where('date_resigned', '>=', $today->copy()->subMonths(6))
            ->count();
        $turnoverRate = $totalEmployees > 0
            ? round(($departures6m / $totalEmployees) * 100, 1)
            : 0;

        $latestPeriod = PayrollPeriod::where('company_id', $companyId)->latest()->first();
        $payrollStatus = $latestPeriod ? ucfirst($latestPeriod->status) : 'None';

        // Department headcount — only departments that actually have active staff.
        $departmentHeadcount = Department::where('company_id', $companyId)
            ->where('is_active', true)
            ->withCount(['employees' => fn ($q) => $q->where('employment_status', 'active')])
            ->orderByDesc('employees_count')
            ->get(['id', 'name'])
            ->filter(fn ($d) => (int) $d->employees_count > 0)
            ->map(fn ($d) => ['name' => $d->name, 'value' => (int) $d->employees_count])
            ->values();

        $monthlyMovement = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $monthlyMovement[] = [
                'month' => $month->format('M'),
                'hires' => Employee::where('company_id', $companyId)
                    ->whereYear('date_hired', $month->year)
                    ->whereMonth('date_hired', $month->month)
                    ->count(),
                'departures' => Employee::where('company_id', $companyId)
                    ->whereNotNull('date_resigned')
                    ->whereYear('date_resigned', $month->year)
                    ->whereMonth('date_resigned', $month->month)
                    ->count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'range' => $range,
            'stats' => [
                'total_employees' => $totalEmployees,
                'total_departments' => $totalDepartments,
                'attendance_today_pct' => $attendanceTodayPct,
                'pending_leaves' => $pendingLeaves,
                'pending_overtime' => $pendingOvertime,
                'pending_approvals' => $pendingLeaves + $pendingOvertime,
                'turnover_rate' => $turnoverRate,
                'payroll_status' => $payrollStatus,
            ],
            'department_headcount' => $departmentHeadcount,
            'monthly_movement' => $monthlyMovement,
            'attendance_trend' => $this->attendanceTrend($companyId, $rangeStart, $today),
            'payroll_summary' => $this->payrollSummary($companyId),
            'pending_approvals' => $this->pendingApprovals($companyId),
        ]);
    }

    /**
     * Daily present / late / absent counts across the selected range.
     *
     * @return list<array{date: string, present: int, late: int, absent: int}>
     */
    private function attendanceTrend(int $companyId, Carbon $start, Carbon $end): array
    {
        $rows = AttendanceRecord::where('company_id', $companyId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('date, status, COUNT(*) as c')
            ->groupBy('date', 'status')
            ->get();

        $byDate = [];
        foreach ($rows as $row) {
            $key = Carbon::parse($row->date)->toDateString();
            $byDate[$key][$row->status] = (int) $row->c;
        }

        $trend = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $trend[] = [
                'date' => $cursor->format('M j'),
                'present' => $byDate[$key]['present'] ?? 0,
                'late' => $byDate[$key]['late'] ?? 0,
                'absent' => $byDate[$key]['absent'] ?? 0,
            ];
            $cursor->addDay();
        }

        return $trend;
    }

    /**
     * Totals from the most recently finalized payroll period.
     *
     * @return array{period: string, pay_date: string, headcount: int, gross_total: float, deductions_total: float, net_total: float}|null
     */
    private function payrollSummary(int $companyId): ?array
    {
        $period = PayrollPeriod::where('company_id', $companyId)
            ->where('status', 'finalized')
            ->latest('pay_date')
            ->first();

        if (! $period) {
            return null;
        }

        $items = PayrollItem::where('payroll_period_id', $period->id);

        return [
            'period' => $period->start_date->format('M j').' – '.$period->end_date->format('M j'),
            'pay_date' => $period->pay_date->toDateString(),
            'headcount' => $items->count(),
            'gross_total' => (float) $items->sum('gross_pay'),
            'deductions_total' => (float) $items->sum('total_deductions'),
            'net_total' => (float) $items->sum('net_pay'),
        ];
    }

    /**
     * Recent pending leave and overtime requests awaiting approval.
     *
     * @return array{leaves: list<array<string, mixed>>, overtime: list<array<string, mixed>>}
     */
    private function pendingApprovals(int $companyId): array
    {
        $leaves = LeaveRequest::with(['employee:id,first_name,last_name', 'leaveType:id,name'])
            ->whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'pending')
            ->latest('requested_at')
            ->limit(5)
            ->get()
            ->map(fn ($l) => [
                'employee' => trim(($l->employee?->first_name ?? '').' '.($l->employee?->last_name ?? '')),
                'type' => $l->leaveType?->name ?? 'Leave',
                'date' => $l->start_date?->toDateString(),
                'days' => (float) $l->total_days,
            ])->values();

        $overtime = OvertimeRecord::with(['employee:id,first_name,last_name'])
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($o) => [
                'employee' => trim(($o->employee?->first_name ?? '').' '.($o->employee?->last_name ?? '')),
                'type' => ucfirst((string) $o->overtime_type).' OT',
                'date' => $o->date?->toDateString(),
                'hours' => (float) $o->hours,
            ])->values();

        return ['leaves' => $leaves->all(), 'overtime' => $overtime->all()];
    }

    private function managerDashboard(mixed $user): Response
    {
        $managerEmployee = Employee::where('user_id', $user->id)->first();
        $subordinateIds = $managerEmployee
            ? Employee::where('supervisor_id', $managerEmployee->id)->pluck('id')
            : collect();

        $today = now()->toDateString();
        $teamPresent = AttendanceRecord::whereIn('employee_id', $subordinateIds)
            ->whereDate('date', $today)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $teamTotal = $subordinateIds->count();
        $teamAttendancePct = $teamTotal > 0 ? round(($teamPresent / $teamTotal) * 100, 1) : 0;

        $pendingLeaveCount = LeaveRequest::whereIn('employee_id', $subordinateIds)
            ->where('status', 'pending')
            ->count();

        $pendingOtCount = OvertimeRecord::whereIn('employee_id', $subordinateIds)
            ->where('status', 'pending')
            ->count();

        return Inertia::render('Dashboard/ManagerDashboard', [
            'teamTotal' => $teamTotal,
            'teamPresent' => $teamPresent,
            'teamAttendancePct' => $teamAttendancePct,
            'pendingLeaveCount' => $pendingLeaveCount,
            'pendingOtCount' => $pendingOtCount,
        ]);
    }

    private function employeeDashboard(mixed $user): Response
    {
        $employee = Employee::where('user_id', $user->id)
            ->with(['department:id,name', 'position:id,position_name'])
            ->first();

        $today = now()->toDateString();
        $todayAttendance = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)->whereDate('date', $today)->first()
            : null;

        $leaveBalances = $employee
            ? LeaveBalance::where('employee_id', $employee->id)->with('leaveType:id,name,is_paid')->get(['id', 'leave_type_id', 'remaining_days', 'used_days'])
            : collect();

        $recentLeaveRequests = $employee
            ? LeaveRequest::where('employee_id', $employee->id)->latest()->limit(5)->get(['id', 'status', 'start_date', 'end_date', 'total_days'])
            : collect();

        $latestPayroll = $employee
            ? PayrollPeriod::where('company_id', $user->company_id)->where('status', 'finalized')->latest()->first(['id', 'start_date', 'end_date'])
            : null;

        $startOfMonth = now()->startOfMonth()->toDateString();
        $startOfYear = now()->startOfYear()->toDateString();

        $lateThisMonth = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)
                ->where('status', 'late')
                ->whereBetween('date', [$startOfMonth, $today])
                ->count()
            : 0;

        $lateYTD = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)
                ->where('status', 'late')
                ->whereBetween('date', [$startOfYear, $today])
                ->count()
            : 0;

        $absencesThisMonth = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)
                ->where('status', 'absent')
                ->whereBetween('date', [$startOfMonth, $today])
                ->count()
            : 0;

        $absencesYTD = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)
                ->where('status', 'absent')
                ->whereBetween('date', [$startOfYear, $today])
                ->count()
            : 0;

        return Inertia::render('Dashboard/EmployeeDashboard', [
            'todayAttendance' => $todayAttendance,
            'leaveBalances' => $leaveBalances,
            'recentLeaveRequests' => $recentLeaveRequests,
            'latestPayroll' => $latestPayroll,
            'lateThisMonth' => $lateThisMonth,
            'lateYTD' => $lateYTD,
            'absencesThisMonth' => $absencesThisMonth,
            'absencesYTD' => $absencesYTD,
            'employeeProfile' => $employee ? [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'full_name' => $employee->full_name,
                'department' => $employee->department?->name,
                'position' => $employee->position?->position_name,
                'employment_status' => $employee->employment_status,
                'date_hired' => $employee->date_hired?->toDateString(),
                'profile_photo_url' => $employee->profile_photo_url,
            ] : null,
        ]);
    }
}
