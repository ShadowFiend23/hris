<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\License;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\LeaveBalance;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $notifications = [];

        if ($user->hasRole('admin')) {
            $notifications = $this->adminNotifications($user);
        } elseif ($user->hasRole('manager')) {
            $notifications = $this->managerNotifications($user);
        } else {
            $notifications = $this->employeeNotifications($user);
        }

        return response()->json([
            'data' => $notifications,
            'unread_count' => count(array_filter($notifications, fn ($n) => ! $n['read'])),
        ]);
    }

    private function adminNotifications(mixed $user): array
    {
        $notifications = [];

        // Pending leave requests
        $companyEmployeeIds = Employee::where('company_id', $user->company_id)->pluck('id');
        $pendingLeave = LeaveRequest::whereIn('employee_id', $companyEmployeeIds)
            ->where('status', 'pending')
            ->count();

        if ($pendingLeave > 0) {
            $notifications[] = [
                'id' => 'admin-leave-pending',
                'type' => 'warning',
                'title' => 'Pending Leave Requests',
                'message' => "{$pendingLeave} leave request(s) awaiting approval.",
                'read' => false,
                'created_at' => now()->toISOString(),
            ];
        }

        // Draft payroll periods
        $draftPayroll = PayrollPeriod::where('company_id', $user->company_id)
            ->where('status', 'draft')
            ->count();

        if ($draftPayroll > 0) {
            $notifications[] = [
                'id' => 'admin-payroll-draft',
                'type' => 'info',
                'title' => 'Draft Payroll Periods',
                'message' => "{$draftPayroll} payroll period(s) in draft — ready to finalize.",
                'read' => false,
                'created_at' => now()->toISOString(),
            ];
        }

        // Recently finalized payroll (last 7 days)
        $recentPayroll = PayrollPeriod::where('company_id', $user->company_id)
            ->where('status', 'finalized')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        if ($recentPayroll > 0) {
            $notifications[] = [
                'id' => 'admin-payroll-finalized',
                'type' => 'success',
                'title' => 'Payroll Finalized',
                'message' => "{$recentPayroll} payroll period(s) finalized in the last 7 days.",
                'read' => false,
                'created_at' => now()->toISOString(),
            ];
        }

        // Licenses expiring in 30 days or already expired
        $expiringLicenses = License::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->where('valid_until', '<=', now()->addDays(30))
            ->where('valid_until', '>=', now())
            ->count();

        $expiredLicenses = License::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->where('valid_until', '<', now())
            ->count();

        if ($expiredLicenses > 0) {
            $notifications[] = [
                'id' => 'admin-license-expired',
                'type' => 'error',
                'title' => 'Licenses Expired',
                'message' => "{$expiredLicenses} license(s) have expired.",
                'read' => false,
                'created_at' => now()->toISOString(),
            ];
        }

        if ($expiringLicenses > 0) {
            $notifications[] = [
                'id' => 'admin-license-expiring',
                'type' => 'warning',
                'title' => 'Licenses Expiring Soon',
                'message' => "{$expiringLicenses} license(s) expiring within 30 days.",
                'read' => false,
                'created_at' => now()->toISOString(),
            ];
        }

        return $notifications;
    }

    private function managerNotifications(mixed $user): array
    {
        $notifications = [];
        $managerEmployee = Employee::where('user_id', $user->id)->first();

        if (! $managerEmployee) {
            return $notifications;
        }

        $subordinateIds = Employee::where('supervisor_id', $managerEmployee->id)->pluck('id');

        if ($subordinateIds->isEmpty()) {
            return $notifications;
        }

        // Pending leave from direct reports
        $pendingLeave = LeaveRequest::whereIn('employee_id', $subordinateIds)
            ->where('status', 'pending')
            ->count();

        if ($pendingLeave > 0) {
            $notifications[] = [
                'id' => 'manager-leave-pending',
                'type' => 'warning',
                'title' => 'Team Leave Requests',
                'message' => "{$pendingLeave} leave request(s) from your direct reports awaiting approval.",
                'read' => false,
                'created_at' => now()->toISOString(),
            ];
        }

        // Pending overtime from direct reports
        $pendingOt = OvertimeRecord::whereIn('employee_id', $subordinateIds)
            ->where('status', 'pending')
            ->count();

        if ($pendingOt > 0) {
            $notifications[] = [
                'id' => 'manager-ot-pending',
                'type' => 'info',
                'title' => 'Team Overtime Requests',
                'message' => "{$pendingOt} overtime request(s) from your direct reports awaiting approval.",
                'read' => false,
                'created_at' => now()->toISOString(),
            ];
        }

        // Direct reports absent today (no attendance record)
        $today = now()->toDateString();
        $presentToday = AttendanceRecord::whereIn('employee_id', $subordinateIds)
            ->whereDate('date', $today)
            ->whereIn('status', ['present', 'late', 'half_day', 'on_leave'])
            ->pluck('employee_id');

        $absentCount = $subordinateIds->diff($presentToday)->count();

        if ($absentCount > 0) {
            $notifications[] = [
                'id' => 'manager-absent-today',
                'type' => 'error',
                'title' => 'Absent Direct Reports',
                'message' => "{$absentCount} of your direct report(s) have no attendance record for today.",
                'read' => false,
                'created_at' => now()->toISOString(),
            ];
        }

        return $notifications;
    }

    private function employeeNotifications(mixed $user): array
    {
        $notifications = [];
        $employee = Employee::where('user_id', $user->id)->first();

        if (! $employee) {
            return $notifications;
        }

        $since7 = now()->subDays(7);

        // Recently approved/rejected leave
        $recentLeave = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('updated_at', '>=', $since7)
            ->get(['id', 'status', 'leave_type_id', 'updated_at']);

        foreach ($recentLeave as $leave) {
            $statusLabel = ucfirst($leave->status);
            $notifications[] = [
                'id' => "leave-{$leave->id}",
                'type' => $leave->status === 'approved' ? 'success' : 'error',
                'title' => "Leave Request {$statusLabel}",
                'message' => "Your leave request has been {$leave->status}.",
                'read' => false,
                'created_at' => $leave->updated_at->toISOString(),
            ];
        }

        // Recently approved/rejected overtime
        $recentOt = OvertimeRecord::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('updated_at', '>=', $since7)
            ->get(['id', 'status', 'updated_at']);

        foreach ($recentOt as $ot) {
            $statusLabel = ucfirst($ot->status);
            $notifications[] = [
                'id' => "ot-{$ot->id}",
                'type' => $ot->status === 'approved' ? 'success' : 'error',
                'title' => "Overtime Request {$statusLabel}",
                'message' => "Your overtime request has been {$ot->status}.",
                'read' => false,
                'created_at' => $ot->updated_at->toISOString(),
            ];
        }

        // Low leave balance (≤3 days remaining)
        $totalBalance = LeaveBalance::where('employee_id', $employee->id)
            ->sum('remaining_days');

        if ($totalBalance <= 3) {
            $notifications[] = [
                'id' => 'low-leave-balance',
                'type' => 'warning',
                'title' => 'Low Leave Balance',
                'message' => "You have only {$totalBalance} leave day(s) remaining.",
                'read' => false,
                'created_at' => now()->toISOString(),
            ];
        }

        return $notifications;
    }
}
