<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\LeaveBalance;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return $this->adminDashboard($user);
        }

        if ($user->hasRole('manager')) {
            return $this->managerDashboard($user);
        }

        return $this->employeeDashboard($user);
    }

    private function adminDashboard(mixed $user): Response
    {
        $companyId = $user->company_id;

        $totalEmployees = Employee::where('company_id', $companyId)
            ->where('employment_status', 'active')
            ->count();

        $totalDepartments = Department::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $today = now()->toDateString();
        $presentToday = AttendanceRecord::where('company_id', $companyId)
            ->whereDate('date', $today)
            ->whereIn('status', ['present', 'late'])
            ->count();
        $attendanceTodayPct = $totalEmployees > 0
            ? round(($presentToday / $totalEmployees) * 100, 1)
            : 0;

        $pendingLeaves = LeaveRequest::whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'pending')
            ->count();

        $latestPeriod = PayrollPeriod::where('company_id', $companyId)
            ->latest()
            ->first();
        $payrollStatus = $latestPeriod ? ucfirst($latestPeriod->status) : 'None';

        $departmentHeadcount = Department::where('company_id', $companyId)
            ->where('is_active', true)
            ->withCount(['employees' => fn ($q) => $q->where('employment_status', 'active')])
            ->orderByDesc('employees_count')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['name' => $d->name, 'value' => $d->employees_count])
            ->values();

        $monthlyMovement = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
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
            'stats' => [
                'total_employees' => $totalEmployees,
                'total_departments' => $totalDepartments,
                'attendance_today_pct' => $attendanceTodayPct,
                'pending_leaves' => $pendingLeaves,
                'payroll_status' => $payrollStatus,
            ],
            'department_headcount' => $departmentHeadcount,
            'monthly_movement' => $monthlyMovement,
        ]);
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
