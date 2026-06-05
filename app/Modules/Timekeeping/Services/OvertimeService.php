<?php

namespace App\Modules\Timekeeping\Services;

use App\Models\User;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use App\Modules\Timekeeping\Models\WorkPolicy;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OvertimeService
{
    /**
     * Create an overtime request
     */
    public function createOvertimeRequest(Employee $employee, array $data): OvertimeRecord
    {
        $date = Carbon::parse($data['date']);
        $overtimeType = $data['overtime_type'] ?? $this->determineOvertimeType($date);

        // Get pay rate multiplier from work policy
        $policy = WorkPolicy::forCompany($employee->company_id)->active()->first();
        $payRateMultiplier = $policy
            ? $policy->getOvertimeRate($overtimeType)
            : $this->getDefaultRate($overtimeType);

        $record = OvertimeRecord::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'date' => $date,
            'hours' => $data['hours'],
            'overtime_type' => $overtimeType,
            'reason' => $data['reason'] ?? null,
            'pay_rate_multiplier' => $payRateMultiplier,
            'status' => 'pending',
        ]);

        Log::info('Overtime request created', [
            'employee_id' => $employee->id,
            'hours' => $data['hours'],
            'type' => $overtimeType,
        ]);

        return $record->fresh(['employee', 'company']);
    }

    /**
     * Approve an overtime request
     */
    public function approveOvertimeRequest(OvertimeRecord $record, User $approver): OvertimeRecord
    {
        if (! $record->isPending()) {
            throw new \Exception('Overtime request is not pending.');
        }

        $record->approve($approver);

        Log::info('Overtime request approved', [
            'record_id' => $record->id,
            'approved_by' => $approver->id,
        ]);

        return $record->fresh(['employee', 'company', 'approver']);
    }

    /**
     * Reject an overtime request
     */
    public function rejectOvertimeRequest(OvertimeRecord $record, User $approver, ?string $reason = null): OvertimeRecord
    {
        if (! $record->isPending()) {
            throw new \Exception('Overtime request is not pending.');
        }

        $record->reject($approver, $reason);

        Log::info('Overtime request rejected', [
            'record_id' => $record->id,
            'rejected_by' => $approver->id,
        ]);

        return $record->fresh(['employee', 'company', 'approver']);
    }

    /**
     * Calculate overtime pay
     */
    public function calculateOvertimePay(OvertimeRecord $record, Employee $employee): float
    {
        // Get hourly rate from employee salary (assuming monthly salary / 160 hours)
        $hourlyRate = ($employee->salary ?? 0) / 160;

        return $record->calculateCompensation($hourlyRate);
    }

    /**
     * Mark overtime as paid
     */
    public function markAsPaid(OvertimeRecord $record): OvertimeRecord
    {
        if (! $record->isApproved()) {
            throw new \Exception('Overtime must be approved before marking as paid.');
        }

        $record->markAsPaid();

        Log::info('Overtime marked as paid', [
            'record_id' => $record->id,
        ]);

        return $record->fresh();
    }

    /**
     * Get overtime summary for an employee
     */
    public function getOvertimeSummary(Employee $employee, Carbon $startDate, Carbon $endDate): array
    {
        $records = OvertimeRecord::forEmployee($employee->id)
            ->forDateRange($startDate, $endDate)
            ->get();

        $approvedRecords = $records->where('status', 'approved');
        $paidRecords = $records->where('status', 'paid');

        return [
            'total_requests' => $records->count(),
            'pending_requests' => $records->where('status', 'pending')->count(),
            'approved_requests' => $approvedRecords->count(),
            'rejected_requests' => $records->where('status', 'rejected')->count(),
            'total_hours' => $approvedRecords->sum('hours') + $paidRecords->sum('hours'),
            'pending_hours' => $records->where('status', 'pending')->sum('hours'),
            'paid_hours' => $paidRecords->sum('hours'),
            'total_by_type' => [
                'weekday' => $records->where('overtime_type', 'weekday')->sum('hours'),
                'weekend' => $records->where('overtime_type', 'weekend')->sum('hours'),
                'holiday' => $records->where('overtime_type', 'holiday')->sum('hours'),
            ],
        ];
    }

    /**
     * Get pending overtime requests for a company
     */
    public function getPendingOvertimeRequests(int $companyId): Collection
    {
        return OvertimeRecord::pending()
            ->forCompany($companyId)
            ->with(['employee'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get overtime history for an employee
     */
    public function getOvertimeHistory(Employee $employee, array $filters = []): LengthAwarePaginator
    {
        $query = OvertimeRecord::forEmployee($employee->id)
            ->with(['approver'])
            ->orderByDesc('date');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['overtime_type'])) {
            $query->where('overtime_type', $filters['overtime_type']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->forDateRange($filters['start_date'], $filters['end_date']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Determine overtime type based on date
     */
    private function determineOvertimeType(Carbon $date): string
    {
        if ($date->isWeekend()) {
            return 'weekend';
        }

        // TODO: Check against holiday list
        return 'weekday';
    }

    /**
     * Get default overtime rate
     */
    private function getDefaultRate(string $type): float
    {
        return match ($type) {
            'weekday' => 1.25,
            'weekend' => 1.50,
            'holiday' => 2.00,
            default => 1.25,
        };
    }
}
