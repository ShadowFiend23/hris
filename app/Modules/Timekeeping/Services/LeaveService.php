<?php

namespace App\Modules\Timekeeping\Services;

use App\Models\User;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\LeaveBalance;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveService
{
    /**
     * Create a new leave request
     */
    public function createLeaveRequest(Employee $employee, array $data): LeaveRequest
    {
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $totalDays = $this->calculateLeaveDays($startDate, $endDate);

        // Check leave balance
        $balance = $this->getLeaveBalanceForType($employee, $leaveType->id, $startDate->year);

        if ($balance && ! $balance->canTakeLeave($totalDays)) {
            throw new \Exception('Insufficient leave balance. Available: '.$balance->remaining_days.' days.');
        }

        $request = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        Log::info('Leave request created', [
            'employee_id' => $employee->id,
            'leave_type' => $leaveType->name,
            'days' => $totalDays,
        ]);

        return $request->fresh(['employee', 'leaveType']);
    }

    /**
     * Approve a leave request
     */
    public function approveLeaveRequest(LeaveRequest $request, User $approver): LeaveRequest
    {
        if (! $request->isPending()) {
            throw new \Exception('Leave request is not pending.');
        }

        return DB::transaction(function () use ($request, $approver) {
            // Deduct from balance
            $balance = $this->getLeaveBalanceForType(
                $request->employee,
                $request->leave_type_id,
                Carbon::parse($request->start_date)->year
            );

            if ($balance) {
                $balance->deductDays($request->total_days);
            }

            // Approve the request
            $request->approve($approver);

            Log::info('Leave request approved', [
                'request_id' => $request->id,
                'approved_by' => $approver->id,
            ]);

            return $request->fresh(['employee', 'leaveType', 'approver']);
        });
    }

    /**
     * Reject a leave request
     */
    public function rejectLeaveRequest(LeaveRequest $request, User $approver, string $reason): LeaveRequest
    {
        if (! $request->isPending()) {
            throw new \Exception('Leave request is not pending.');
        }

        $request->reject($approver, $reason);

        Log::info('Leave request rejected', [
            'request_id' => $request->id,
            'rejected_by' => $approver->id,
            'reason' => $reason,
        ]);

        return $request->fresh(['employee', 'leaveType', 'approver']);
    }

    /**
     * Cancel a leave request
     */
    public function cancelLeaveRequest(LeaveRequest $request): LeaveRequest
    {
        if ($request->isApproved()) {
            // Restore balance if already approved
            $balance = $this->getLeaveBalanceForType(
                $request->employee,
                $request->leave_type_id,
                Carbon::parse($request->start_date)->year
            );

            if ($balance) {
                $balance->addDays($request->total_days);
            }
        }

        $request->cancel();

        Log::info('Leave request cancelled', [
            'request_id' => $request->id,
        ]);

        return $request->fresh();
    }

    /**
     * Get leave balances for an employee for a specific year
     */
    public function getLeaveBalance(Employee $employee, int $year): Collection
    {
        return LeaveBalance::forEmployee($employee->id)
            ->forYear($year)
            ->with('leaveType')
            ->get();
    }

    /**
     * Get leave balance for a specific leave type
     */
    public function getLeaveBalanceForType(Employee $employee, int $leaveTypeId, int $year): ?LeaveBalance
    {
        return LeaveBalance::forEmployee($employee->id)
            ->forYear($year)
            ->where('leave_type_id', $leaveTypeId)
            ->first();
    }

    /**
     * Initialize leave balances for a new employee or new year
     */
    public function initializeLeaveBalances(Employee $employee, int $year): Collection
    {
        $leaveTypes = LeaveType::forCompany($employee->company_id)
            ->active()
            ->get();

        $balances = collect();

        foreach ($leaveTypes as $leaveType) {
            $existingBalance = LeaveBalance::forEmployee($employee->id)
                ->forYear($year)
                ->where('leave_type_id', $leaveType->id)
                ->first();

            if (! $existingBalance) {
                // Check for carry over from previous year
                $previousYearBalance = LeaveBalance::forEmployee($employee->id)
                    ->forYear($year - 1)
                    ->where('leave_type_id', $leaveType->id)
                    ->first();

                $carriedOver = $previousYearBalance
                    ? min($previousYearBalance->remaining_days, 5) // Max 5 days carry over
                    : 0;

                $balance = LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                    'total_days' => $leaveType->days_per_year,
                    'used_days' => 0,
                    'remaining_days' => $leaveType->days_per_year + $carriedOver,
                    'carried_over_days' => $carriedOver,
                ]);

                $balances->push($balance);
            } else {
                $balances->push($existingBalance);
            }
        }

        return $balances->load('leaveType');
    }

    /**
     * Calculate leave days between two dates (excluding weekends)
     */
    public function calculateLeaveDays(Carbon $startDate, Carbon $endDate): float
    {
        $days = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if (! $current->isWeekend()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Get pending leave requests for a company
     */
    public function getPendingRequests(int $companyId): Collection
    {
        return LeaveRequest::pending()
            ->whereHas('employee', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['employee', 'leaveType'])
            ->orderBy('requested_at')
            ->get();
    }

    /**
     * Get employee leave history with filters
     */
    public function getEmployeeLeaveHistory(Employee $employee, array $filters = []): LengthAwarePaginator
    {
        $query = LeaveRequest::forEmployee($employee->id)
            ->with(['leaveType', 'approver'])
            ->orderByDesc('requested_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        if (! empty($filters['year'])) {
            $query->whereYear('start_date', $filters['year']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get leave types for a company
     */
    public function getLeaveTypes(int $companyId): Collection
    {
        return LeaveType::forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get();
    }
}
