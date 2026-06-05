<?php

namespace App\Modules\Timekeeping\Policies;

use App\Models\User;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use App\Modules\Timekeeping\Models\ShiftSwapRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimekeepingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view their own timekeeping data.
     */
    public function viewOwn(User $user): bool
    {
        return $user->employee !== null;
    }

    /**
     * Determine if the user can manage their own timekeeping data (clock in/out, leave requests, etc.).
     */
    public function manageOwn(User $user): bool
    {
        return $user->employee !== null;
    }

    /**
     * Determine if the user can view any employee's timekeeping data.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('timekeeping.view_all');
    }

    /**
     * Determine if the user can manage all timekeeping data (approve requests, etc.).
     */
    public function manage(User $user): bool
    {
        return $user->hasPermission('timekeeping.approve_attendance')
            || $user->hasPermission('timekeeping.approve_leave')
            || $user->hasPermission('timekeeping.approve_overtime');
    }

    /**
     * Determine if the user can view a specific attendance record.
     */
    public function viewAttendance(User $user, AttendanceRecord $record): bool
    {
        // Can view own attendance
        if ($user->employee && (int) $user->employee->id === (int) $record->employee_id) {
            return true;
        }

        // Can view if same company (admin/manager)
        if ($user->employee && (int) $user->employee->company_id === (int) $record->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can update a specific attendance record.
     */
    public function updateAttendance(User $user, AttendanceRecord $record): bool
    {
        return $user->hasPermission('timekeeping.approve_attendance')
            && $user->employee
            && (int) $user->employee->company_id === (int) $record->company_id;
    }

    /**
     * Determine if the user can view a specific leave request.
     */
    public function viewLeaveRequest(User $user, LeaveRequest $request): bool
    {
        // Can view own leave request
        if ($user->employee && (int) $user->employee->id === (int) $request->employee_id) {
            return true;
        }

        // Can view if same company (admin/manager)
        $employee = Employee::find($request->employee_id);
        if ($user->employee && $employee && (int) $user->employee->company_id === (int) $employee->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can approve/reject leave requests.
     */
    public function manageLeaveRequest(User $user, LeaveRequest $request): bool
    {
        $employee = Employee::find($request->employee_id);

        return $user->hasPermission('timekeeping.approve_leave')
            && $user->employee
            && $employee
            && (int) $user->employee->company_id === (int) $employee->company_id;
    }

    /**
     * Determine if the user can cancel a leave request.
     */
    public function cancelLeaveRequest(User $user, LeaveRequest $request): bool
    {
        // Can only cancel own leave request
        return $user->employee && (int) $user->employee->id === (int) $request->employee_id;
    }

    /**
     * Determine if the user can view an overtime record.
     */
    public function viewOvertime(User $user, OvertimeRecord $record): bool
    {
        // Can view own overtime
        if ($user->employee && (int) $user->employee->id === (int) $record->employee_id) {
            return true;
        }

        // Can view if same company (admin/manager)
        if ($user->employee && (int) $user->employee->company_id === (int) $record->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can manage overtime requests.
     */
    public function manageOvertime(User $user, OvertimeRecord $record): bool
    {
        return $user->hasPermission('timekeeping.approve_overtime')
            && $user->employee
            && (int) $user->employee->company_id === (int) $record->company_id;
    }

    /**
     * Determine if the user can cancel an overtime request.
     */
    public function cancelOvertime(User $user, OvertimeRecord $record): bool
    {
        // Can only cancel own pending overtime request
        return $user->employee
            && (int) $user->employee->id === (int) $record->employee_id
            && $record->status === 'pending';
    }

    /**
     * Determine if the user can view a shift swap request.
     */
    public function viewShiftSwap(User $user, ShiftSwapRequest $request): bool
    {
        // Can view if requester or target
        if ($user->employee) {
            $employeeId = (int) $user->employee->id;
            if ($employeeId === (int) $request->requester_id || $employeeId === (int) $request->target_employee_id) {
                return true;
            }
        }

        // Can view if same company (admin/manager)
        $requester = Employee::find($request->requester_id);
        if ($user->employee && $requester && (int) $user->employee->company_id === (int) $requester->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can manage shift swap requests.
     */
    public function manageShiftSwap(User $user, ShiftSwapRequest $request): bool
    {
        $requester = Employee::find($request->requester_id);

        return $user->hasPermission('timekeeping.manage_shifts')
            && $user->employee
            && $requester
            && (int) $user->employee->company_id === (int) $requester->company_id;
    }

    /**
     * Determine if the user can access reports.
     */
    public function viewReports(User $user): bool
    {
        // TODO: Check for admin/manager role
        return $user->employee !== null;
    }

    /**
     * Determine if the user can access their own summary report.
     */
    public function viewOwnReport(User $user): bool
    {
        return $user->employee !== null;
    }
}
