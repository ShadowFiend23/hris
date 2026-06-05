<?php

namespace App\Modules\Timekeeping\Services;

use App\Models\User;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\EmployeeSchedule;
use App\Modules\Timekeeping\Models\ShiftSwapRequest;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftService
{
    /**
     * Create a shift template
     */
    public function createShiftTemplate(int $companyId, array $data): ShiftTemplate
    {
        return ShiftTemplate::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_hours' => $data['duration_hours'] ?? 8,
            'break_duration' => $data['break_duration'] ?? 60,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Assign a shift to an employee for a specific date
     */
    public function assignShift(Employee $employee, ShiftTemplate $shift, Carbon $date): EmployeeSchedule
    {
        return EmployeeSchedule::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $date->toDateString(),
            ],
            [
                'shift_template_id' => $shift->id,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'status' => 'scheduled',
            ]
        );
    }

    /**
     * Bulk assign shifts to multiple employees
     */
    public function bulkAssignShifts(array $employeeIds, ShiftTemplate $shift, Carbon $startDate, Carbon $endDate): Collection
    {
        $schedules = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if (! $current->isWeekend()) {
                foreach ($employeeIds as $employeeId) {
                    $schedule = EmployeeSchedule::updateOrCreate(
                        [
                            'employee_id' => $employeeId,
                            'date' => $current->toDateString(),
                        ],
                        [
                            'shift_template_id' => $shift->id,
                            'start_time' => $shift->start_time,
                            'end_time' => $shift->end_time,
                            'status' => 'scheduled',
                        ]
                    );
                    $schedules->push($schedule);
                }
            }
            $current->addDay();
        }

        Log::info('Bulk shift assignment completed', [
            'employee_count' => count($employeeIds),
            'shift_id' => $shift->id,
            'date_range' => $startDate->toDateString().' to '.$endDate->toDateString(),
        ]);

        return $schedules;
    }

    /**
     * Get employee schedule for a date range
     */
    public function getEmployeeSchedule(Employee $employee, Carbon $startDate, Carbon $endDate): Collection
    {
        return EmployeeSchedule::forEmployee($employee->id)
            ->forDateRange($startDate, $endDate)
            ->with('shiftTemplate')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get weekly schedule for an employee
     */
    public function getWeeklySchedule(Employee $employee, Carbon $weekStart): Collection
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        return $this->getEmployeeSchedule($employee, $weekStart, $weekEnd);
    }

    /**
     * Create a shift swap request
     */
    public function requestShiftSwap(
        Employee $requester,
        EmployeeSchedule $requesterSchedule,
        Employee $target,
        EmployeeSchedule $targetSchedule,
        ?string $reason = null
    ): ShiftSwapRequest {
        // Validate that both schedules are in the future
        if ($requesterSchedule->date < now()->toDateString() || $targetSchedule->date < now()->toDateString()) {
            throw new \Exception('Cannot swap shifts for past dates.');
        }

        // Validate that schedules are not already swapped
        if ($requesterSchedule->status === 'swapped' || $targetSchedule->status === 'swapped') {
            throw new \Exception('One of the schedules has already been swapped.');
        }

        $swapRequest = ShiftSwapRequest::create([
            'requester_id' => $requester->id,
            'target_employee_id' => $target->id,
            'requester_schedule_id' => $requesterSchedule->id,
            'target_schedule_id' => $targetSchedule->id,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        Log::info('Shift swap request created', [
            'requester_id' => $requester->id,
            'target_id' => $target->id,
        ]);

        return $swapRequest->fresh(['requester', 'targetEmployee', 'requesterSchedule', 'targetSchedule']);
    }

    /**
     * Approve a shift swap request
     */
    public function approveShiftSwap(ShiftSwapRequest $request, User $approver): ShiftSwapRequest
    {
        if (! $request->isPending()) {
            throw new \Exception('Swap request is not pending.');
        }

        return DB::transaction(function () use ($request, $approver) {
            // Swap the schedules
            $requesterSchedule = $request->requesterSchedule;
            $targetSchedule = $request->targetSchedule;

            // Swap employee IDs
            $tempEmployeeId = $requesterSchedule->employee_id;
            $requesterSchedule->update([
                'employee_id' => $targetSchedule->employee_id,
                'status' => 'swapped',
            ]);
            $targetSchedule->update([
                'employee_id' => $tempEmployeeId,
                'status' => 'swapped',
            ]);

            // Approve the request
            $request->approve($approver);

            Log::info('Shift swap approved', [
                'request_id' => $request->id,
                'approved_by' => $approver->id,
            ]);

            return $request->fresh(['requester', 'targetEmployee', 'requesterSchedule', 'targetSchedule', 'approver']);
        });
    }

    /**
     * Reject a shift swap request
     */
    public function rejectShiftSwap(ShiftSwapRequest $request, User $approver, string $reason): ShiftSwapRequest
    {
        if (! $request->isPending()) {
            throw new \Exception('Swap request is not pending.');
        }

        $request->reject($approver, $reason);

        Log::info('Shift swap rejected', [
            'request_id' => $request->id,
            'rejected_by' => $approver->id,
        ]);

        return $request->fresh(['requester', 'targetEmployee', 'approver']);
    }

    /**
     * Get pending swap requests for a company
     */
    public function getPendingSwapRequests(int $companyId): Collection
    {
        return ShiftSwapRequest::pending()
            ->whereHas('requester', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['requester', 'targetEmployee', 'requesterSchedule.shiftTemplate', 'targetSchedule.shiftTemplate'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get shift templates for a company
     */
    public function getShiftTemplates(int $companyId): Collection
    {
        return ShiftTemplate::forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get();
    }

    /**
     * Mark schedule as completed
     */
    public function markScheduleCompleted(EmployeeSchedule $schedule): EmployeeSchedule
    {
        $schedule->update(['status' => 'completed']);

        return $schedule->fresh();
    }

    /**
     * Mark schedule as off
     */
    public function markScheduleOff(EmployeeSchedule $schedule, ?string $notes = null): EmployeeSchedule
    {
        $schedule->update([
            'status' => 'off',
            'notes' => $notes,
        ]);

        return $schedule->fresh();
    }
}
