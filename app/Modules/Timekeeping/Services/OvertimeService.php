<?php

namespace App\Modules\Timekeeping\Services;

use App\Models\User;
use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\PayrollSetting;
use App\Modules\Payroll\Services\HolidayPayService;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use App\Modules\Timekeeping\Models\WorkPolicy;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OvertimeService
{
    public function __construct(
        private readonly HolidayPayService $holidays,
    ) {}

    /**
     * Create an overtime request from a time-in / time-out window.
     *
     * Expects `start_date`, `start_time`, `end_date`, `end_time` (the time-out
     * may fall on the next day for overnight overtime). Worked hours and the
     * overtime type are derived; nothing is taken from the user for those.
     *
     * @param  array{start_date:string,start_time:string,end_date:string,end_time:string,reason?:string|null}  $data
     */
    public function createOvertimeRequest(Employee $employee, array $data): OvertimeRecord
    {
        $startAt = Carbon::parse($data['start_date'].' '.$data['start_time']);
        $endAt = Carbon::parse($data['end_date'].' '.$data['end_time']);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw new \InvalidArgumentException('The time-out must be after the time-in.');
        }

        $date = $startAt->copy()->startOfDay();
        $hours = round($startAt->diffInMinutes($endAt) / 60, 2);
        $overtimeType = $this->determineOvertimeType($employee, $date);

        // Get pay rate multiplier from work policy
        $policy = WorkPolicy::forCompany($employee->company_id)->active()->first();
        $payRateMultiplier = $policy
            ? $policy->getOvertimeRate($overtimeType)
            : $this->getDefaultRate($overtimeType);

        $record = OvertimeRecord::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'date' => $date,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'hours' => $hours,
            'overtime_type' => $overtimeType,
            'reason' => $data['reason'] ?? null,
            'pay_rate_multiplier' => $payRateMultiplier,
            'status' => 'pending',
        ]);

        Log::info('Overtime request created', [
            'employee_id' => $employee->id,
            'hours' => $hours,
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
     * Calculate overtime pay using the same hourly-rate basis as the payroll engine
     * (monthly salary ÷ (work days per month × standard hours per day)).
     */
    public function calculateOvertimePay(OvertimeRecord $record, Employee $employee): float
    {
        $policy = WorkPolicy::forCompany($employee->company_id)->active()->first();
        $standardHours = $policy?->standard_hours_per_day ?? 8;
        $workDaysPerMonth = PayrollSetting::where('company_id', $employee->company_id)->value('work_days_per_month') ?? 26;

        $monthlySalary = $employee->salary_type === 'daily'
            ? (float) ($employee->salary ?? 0) * $workDaysPerMonth
            : (float) ($employee->salary ?? 0);

        $divisor = $workDaysPerMonth * $standardHours;
        $hourlyRate = $divisor > 0 ? $monthlySalary / $divisor : 0.0;

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
     * Determine overtime type for an employee on a given date: holiday takes
     * precedence, then a rest day (a day not in the employee's shift work_days),
     * otherwise a regular weekday.
     */
    private function determineOvertimeType(Employee $employee, Carbon $date): string
    {
        if ($this->holidays->getHolidayForDate($date, $employee->company_id)) {
            return 'holiday';
        }

        $workDays = $employee->shiftTemplate?->work_days ?? [1, 2, 3, 4, 5];

        if (! in_array((int) $date->format('N'), $workDays, true)) {
            return 'weekend';
        }

        return 'weekday';
    }

    /**
     * Get default overtime rate (DOLE-compounded fallback when no work policy exists):
     * ordinary 125%, rest/special day 169% (1.30×1.30), regular holiday 260% (2.00×1.30).
     */
    private function getDefaultRate(string $type): float
    {
        return match ($type) {
            'weekday' => 1.25,
            'weekend' => 1.69,
            'holiday' => 2.60,
            default => 1.25,
        };
    }
}
