<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\EmployeeAllowance;
use App\Modules\Payroll\Models\Loan;
use App\Modules\Payroll\Models\PayrollDeduction;
use App\Modules\Payroll\Models\PayrollEarning;
use App\Modules\Payroll\Models\PayrollItem;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use App\Modules\Timekeeping\Models\WorkPolicy;
use App\Modules\Timekeeping\Services\DtrService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    public function __construct(
        private readonly SSSContributionService $sss,
        private readonly PhilHealthContributionService $philhealth,
        private readonly PagibigContributionService $pagibig,
        private readonly WithholdingTaxService $tax,
        private readonly NightDifferentialService $nightDiff,
        private readonly HolidayPayService $holidays,
        private readonly PayrollPeriodService $periodService,
        private readonly DtrService $dtr,
    ) {}

    /**
     * Compute and persist a PayrollItem for a single employee in a given period.
     */
    public function computeForEmployee(PayrollPeriod $period, Employee $employee): PayrollItem
    {
        $employee->loadMissing('shiftTemplate');

        PayrollItem::where('payroll_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->where('status', 'draft')
            ->delete();

        $workPolicy = WorkPolicy::where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->first();

        $standardHoursPerDay = $workPolicy?->standard_hours_per_day ?? 8;
        $workDaysPerMonth = $period->setting->work_days_per_month ?? 26;
        $cutoffs = $this->periodService->cutoffsPerMonth($period->setting->period_type);

        $monthlyBasicSalary = $this->resolveMonthlyBasicSalary($employee, $workDaysPerMonth);
        $cutoffBasicPay = round($monthlyBasicSalary / $cutoffs, 2);
        $dailyRate = round($monthlyBasicSalary / $workDaysPerMonth, 4);
        $hourlyRate = round($dailyRate / $standardHoursPerDay, 4);

        $regularHolidayRate = (float) ($workPolicy->regular_holiday_rate ?? 2.0);
        $specialHolidayRate = (float) ($workPolicy->special_holiday_rate ?? 1.30);
        $restDayRate = (float) ($workPolicy->rest_day_rate ?? 1.30);
        // Prefer the admin-editable Payroll Setting (Payroll Settings UI); fall back to the
        // work policy, then the 10% DOLE minimum.
        $ndRate = (float) ($period->setting->night_differential_rate ?? $workPolicy->night_differential_rate ?? 0.10);

        $cutoffStart = ($period->cutoff_start_date ?? $period->start_date)->copy();
        $cutoffEnd = ($period->cutoff_end_date ?? $period->end_date)->copy();

        $workDays = $employee->shiftTemplate?->work_days ?? [1, 2, 3, 4, 5];

        /** @var Collection<int, AttendanceRecord> $attendanceRecords */
        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$cutoffStart, $cutoffEnd])
            ->whereNull('deleted_at')
            ->get();

        $attendanceByDate = $attendanceRecords->keyBy(fn (AttendanceRecord $r): string => $r->date->toDateString());

        $totalHours = (float) $attendanceRecords->sum('total_hours');
        $daysPresent = $attendanceRecords->whereIn('status', ['present', 'late'])->count()
            + (0.5 * $attendanceRecords->where('status', 'half_day')->count());

        $holidayMap = $this->buildHolidayMap($cutoffStart, $cutoffEnd, $employee->company_id);
        [$paidLeaveDates, $unpaidLeaveDates] = $this->resolveLeaveDates($employee, $cutoffStart, $cutoffEnd);

        // --- ABSENCES (counted once, only on scheduled work days) ---
        $absenceDeductionDays = $this->countAbsenceDays(
            $cutoffStart,
            $cutoffEnd,
            $workDays,
            $attendanceByDate,
            $holidayMap,
            $paidLeaveDates,
            $unpaidLeaveDates
        );

        // --- EARNINGS ---
        $earnings = [];

        // Basic pay is the full cut-off value; absences are docked once as a deduction below.
        $earnings[] = ['type' => 'basic', 'amount' => round($cutoffBasicPay, 2), 'hours' => null, 'description' => 'Basic pay', 'is_taxable' => true];

        // Overtime — split by type (weekday / weekend / holiday)
        foreach ($this->buildOvertimeEarnings($employee, $cutoffStart, $cutoffEnd, $hourlyRate) as $otEarning) {
            $earnings[] = $otEarning;
        }

        // Night differential & holiday premiums
        $totalNightDiff = 0.0;
        $totalNightDiffHours = 0.0;
        $holidayTotals = [
            'regular' => ['pay' => 0.0, 'hours' => 0.0],
            'special' => ['pay' => 0.0, 'hours' => 0.0],
        ];
        $restDayTotals = ['pay' => 0.0, 'hours' => 0.0];
        $undertimeMinutes = 0;

        foreach ($attendanceRecords as $record) {
            if (! $record->clock_in || ! $record->clock_out) {
                continue;
            }

            $ndPay = $this->nightDiff->compute(
                $record->clock_in,
                $record->clock_out,
                $hourlyRate,
                $ndRate,
                (int) ($record->break_duration ?? 0),
                (float) $record->total_hours
            );
            $totalNightDiff += $ndPay;
            $totalNightDiffHours += $this->nightDiff->computeNightHours(
                $record->clock_in,
                $record->clock_out,
                (int) ($record->break_duration ?? 0),
                (float) $record->total_hours
            );

            // Undertime (late arrival + early departure), for day and overnight shifts alike.
            // Half-days are excluded — the 0.5-day absence already accounts for the missing time.
            if ($record->status !== 'half_day') {
                $undertimeMinutes += $this->dtr->undertimeMinutesForRecord($record, $employee->shiftTemplate, $record->date);
            }
        }

        // Holiday premiums on scheduled work days (rest-day holidays handled below).
        foreach ($holidayMap as $dateStr => $holiday) {
            $date = Carbon::parse($dateStr);

            if (! in_array((int) $date->format('N'), $workDays, true)) {
                continue;
            }

            $record = $attendanceByDate->get($dateStr);
            $worked = $record && in_array($record->status, ['present', 'late', 'half_day'], true);
            $hoursWorked = $worked ? (float) $record->total_hours : 0.0;

            $earning = $this->holidays->computeHolidayEarning(
                $dailyRate,
                $holiday->type,
                $hoursWorked,
                (float) $standardHoursPerDay,
                $regularHolidayRate,
                $specialHolidayRate
            );

            if ($earning <= 0) {
                continue;
            }

            $bucket = $holiday->isRegular() ? 'regular' : 'special';
            $holidayTotals[$bucket]['pay'] += $earning;
            $holidayTotals[$bucket]['hours'] += $hoursWorked;
        }

        // Rest-day premium: hours worked on a non-scheduled day (Labor Code Art. 93),
        // compounding with a holiday when the rest day is also a holiday.
        foreach ($attendanceRecords as $record) {
            if (! in_array($record->status, ['present', 'late', 'half_day'], true)) {
                continue;
            }

            if (in_array((int) $record->date->format('N'), $workDays, true)) {
                continue; // scheduled work day, not a rest day
            }

            $hoursWorked = (float) $record->total_hours;

            if ($hoursWorked <= 0) {
                continue;
            }

            $restDayTotals['pay'] += $this->holidays->computeRestDayEarning(
                $dailyRate,
                $hoursWorked,
                (float) $standardHoursPerDay,
                ($holidayMap[$record->date->toDateString()] ?? null)?->type,
                $restDayRate,
                $regularHolidayRate
            );
            $restDayTotals['hours'] += $hoursWorked;
        }

        if ($totalNightDiff > 0) {
            $earnings[] = ['type' => 'night_differential', 'amount' => round($totalNightDiff, 2), 'hours' => round($totalNightDiffHours, 2), 'description' => 'Night shift differential ('.round($ndRate * 100).'%)', 'is_taxable' => true];
        }

        if ($holidayTotals['regular']['pay'] > 0) {
            $earnings[] = ['type' => 'regular_holiday', 'amount' => round($holidayTotals['regular']['pay'], 2), 'hours' => round($holidayTotals['regular']['hours'], 2), 'description' => 'Regular holiday pay', 'is_taxable' => true];
        }

        if ($holidayTotals['special']['pay'] > 0) {
            $earnings[] = ['type' => 'special_holiday', 'amount' => round($holidayTotals['special']['pay'], 2), 'hours' => round($holidayTotals['special']['hours'], 2), 'description' => 'Special holiday pay', 'is_taxable' => true];
        }

        if ($restDayTotals['pay'] > 0) {
            $earnings[] = ['type' => 'rest_day', 'amount' => round($restDayTotals['pay'], 2), 'hours' => round($restDayTotals['hours'], 2), 'description' => 'Rest day pay', 'is_taxable' => true];
        }

        // Allowances
        $allowances = EmployeeAllowance::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->get();

        foreach ($allowances as $allowance) {
            $allowanceAmount = $allowance->frequency === 'per_cutoff'
                ? (float) $allowance->amount
                : round((float) $allowance->amount / $cutoffs, 2);

            $earnings[] = [
                'type' => 'allowance_'.str_replace(' ', '_', strtolower($allowance->type)),
                'amount' => $allowanceAmount,
                'hours' => null,
                'description' => $allowance->name,
                'is_taxable' => (bool) $allowance->is_taxable,
            ];
        }

        $grossPay = collect($earnings)->sum('amount');

        // --- DEDUCTIONS ---
        $deductions = [];

        $sssContrib = round($this->sss->computeEmployeeShare($monthlyBasicSalary) / $cutoffs, 2);
        $philhealthContrib = $this->philhealth->computeEmployeeSharePerCutoff($monthlyBasicSalary, $cutoffs);
        $pagibigContrib = $this->pagibig->computeEmployeeSharePerCutoff($monthlyBasicSalary, $cutoffs);

        if ($sssContrib > 0) {
            $deductions[] = ['type' => 'sss', 'amount' => $sssContrib, 'description' => 'SSS contribution'];
        }

        if ($philhealthContrib > 0) {
            $deductions[] = ['type' => 'philhealth', 'amount' => $philhealthContrib, 'description' => 'PhilHealth premium'];
        }

        if ($pagibigContrib > 0) {
            $deductions[] = ['type' => 'pagibig', 'amount' => $pagibigContrib, 'description' => 'Pag-IBIG contribution'];
        }

        // Withholding tax — withheld every period via the BIR table for this period type,
        // on this cut-off's taxable compensation net of mandatory contributions.
        $taxableThisCutoff = collect($earnings)->where('is_taxable', true)->sum('amount')
            - $sssContrib - $philhealthContrib - $pagibigContrib;
        $tax = $this->tax->computeForPeriod(max(0, $taxableThisCutoff), $period->setting->period_type);

        if ($tax > 0) {
            $deductions[] = ['type' => 'withholding_tax', 'amount' => $tax, 'description' => 'Withholding tax (BIR)'];
        }

        // Loan amortizations — deduction lines only; balances are applied at processing time.
        $isLastCutoffOfMonth = $this->isLastCutoffOfMonth($period);
        $loansEnabled = (bool) Company::where('id', $employee->company_id)->value('loans_enabled');

        if ($loansEnabled && $isLastCutoffOfMonth) {
            $activeLoans = Loan::where('employee_id', $employee->id)
                ->where('status', 'active')
                ->get();

            foreach ($activeLoans as $loan) {
                $amortization = min((float) $loan->monthly_amortization, (float) $loan->balance);

                if ($amortization > 0) {
                    $loanType = match ($loan->type) {
                        'sss_loan' => 'sss_loan',
                        'pagibig_loan' => 'pagibig_loan',
                        default => 'company_loan',
                    };

                    $deductions[] = [
                        'type' => $loanType,
                        'amount' => round($amortization, 2),
                        'description' => ucfirst(str_replace('_', ' ', $loan->type)).' amortization',
                    ];
                }
            }
        }

        // Tardiness / undertime (partial-day) — docked as minutes, not whole days.
        if ($undertimeMinutes > 0) {
            $undertimeAmount = round(($undertimeMinutes / 60) * $hourlyRate, 2);

            if ($undertimeAmount > 0) {
                $deductions[] = ['type' => 'tardiness', 'amount' => $undertimeAmount, 'description' => "Tardiness/undertime: {$undertimeMinutes} min"];
            }
        }

        // Absence (whole scheduled days not worked / not covered) — docked once.
        if ($absenceDeductionDays > 0) {
            $absenceAmount = round($absenceDeductionDays * $dailyRate, 2);
            $deductions[] = ['type' => 'absence', 'amount' => $absenceAmount, 'description' => "Absence: {$absenceDeductionDays} day(s)"];
        }

        $totalDeductions = collect($deductions)->sum('amount');
        $netPay = max(0, $grossPay - $totalDeductions);

        // --- PERSIST ---
        return DB::transaction(function () use ($period, $employee, $cutoffBasicPay, $grossPay, $totalDeductions, $netPay, $totalHours, $daysPresent, $absenceDeductionDays, $undertimeMinutes, $earnings, $deductions): PayrollItem {
            $item = PayrollItem::create([
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'basic_pay' => round($cutoffBasicPay, 2),
                'gross_pay' => round($grossPay, 2),
                'total_deductions' => round($totalDeductions, 2),
                'net_pay' => round($netPay, 2),
                'total_hours' => round($totalHours, 2),
                'days_worked' => $daysPresent,
                'days_absent' => $absenceDeductionDays,
                'minutes_late' => $undertimeMinutes,
                'status' => 'draft',
            ]);

            foreach ($earnings as $earning) {
                PayrollEarning::create(array_merge(['payroll_item_id' => $item->id], $earning));
            }

            foreach ($deductions as $deduction) {
                PayrollDeduction::create(array_merge(['payroll_item_id' => $item->id], $deduction));
            }

            return $item;
        });
    }

    /**
     * Compute payroll for all active employees in a company for a period.
     */
    public function runPayrollForPeriod(PayrollPeriod $period): void
    {
        $employees = Employee::where('company_id', $period->company_id)
            ->where('is_active', true)
            ->where('employment_status', 'active')
            ->get();

        foreach ($employees as $employee) {
            $this->computeForEmployee($period, $employee);
        }

        // Payslips are generated and open for review; the period stays in "review"
        // until an admin finalizes it. Loan balances are committed at finalization.
        $period->update([
            'status' => 'review',
            'processed_at' => now(),
        ]);
    }

    /**
     * Lock a processed period: commit loan amortizations (once) and mark it finalized.
     */
    public function finalizePeriod(PayrollPeriod $period): void
    {
        $employees = Employee::where('company_id', $period->company_id)
            ->where('is_active', true)
            ->where('employment_status', 'active')
            ->get();

        $this->applyLoanAmortizations($period, $employees);

        $period->update(['status' => 'finalized']);
    }

    /**
     * Apply loan balance reductions once for a period at finalization.
     *
     * @param  Collection<int, Employee>  $employees
     */
    private function applyLoanAmortizations(PayrollPeriod $period, Collection $employees): void
    {
        if (! $this->isLastCutoffOfMonth($period)) {
            return;
        }

        DB::transaction(function () use ($employees): void {
            foreach ($employees as $employee) {
                $loansEnabled = (bool) Company::where('id', $employee->company_id)->value('loans_enabled');

                if (! $loansEnabled) {
                    continue;
                }

                $activeLoans = Loan::where('employee_id', $employee->id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();

                foreach ($activeLoans as $loan) {
                    $amortization = min((float) $loan->monthly_amortization, (float) $loan->balance);

                    if ($amortization <= 0) {
                        continue;
                    }

                    $newBalance = max(0, (float) $loan->balance - $amortization);
                    $loan->update([
                        'balance' => $newBalance,
                        'status' => $newBalance <= 0 ? 'completed' : 'active',
                    ]);
                }
            }
        });
    }

    /**
     * @return array{0: array<string, true>, 1: array<string, true>} [paidLeaveDates, unpaidLeaveDates]
     */
    private function resolveLeaveDates(Employee $employee, Carbon $cutoffStart, Carbon $cutoffEnd): array
    {
        $leaves = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $cutoffEnd)
            ->where('end_date', '>=', $cutoffStart)
            ->get();

        $paid = [];
        $unpaid = [];

        foreach ($leaves as $leave) {
            $isPaid = (bool) ($leave->leaveType?->is_paid ?? true);
            $day = $leave->start_date->copy()->max($cutoffStart);
            $end = $leave->end_date->copy()->min($cutoffEnd);

            while ($day->lte($end)) {
                $key = $day->toDateString();
                if ($isPaid) {
                    $paid[$key] = true;
                } else {
                    $unpaid[$key] = true;
                }
                $day->addDay();
            }
        }

        return [$paid, $unpaid];
    }

    /**
     * Count whole-day absences over the cut-off, only on scheduled work days and only
     * for days not otherwise covered (present, paid leave, or a paid holiday).
     *
     * @param  Collection<string, AttendanceRecord>  $attendanceByDate
     * @param  array<string, \App\Modules\Payroll\Models\Holiday>  $holidayMap
     * @param  array<string, true>  $paidLeaveDates
     * @param  array<string, true>  $unpaidLeaveDates
     * @param  array<int, int>  $workDays
     */
    private function countAbsenceDays(
        Carbon $cutoffStart,
        Carbon $cutoffEnd,
        array $workDays,
        Collection $attendanceByDate,
        array $holidayMap,
        array $paidLeaveDates,
        array $unpaidLeaveDates
    ): float {
        $absence = 0.0;
        $day = $cutoffStart->copy();

        // Never dock days that have not occurred yet (e.g. payroll previewed mid-period).
        $effectiveEnd = $cutoffEnd->copy()->min(Carbon::today());

        while ($day->lte($effectiveEnd)) {
            $dateStr = $day->toDateString();

            if (! in_array((int) $day->format('N'), $workDays, true)) {
                $day->addDay();

                continue;
            }

            $record = $attendanceByDate->get($dateStr);
            $holiday = $holidayMap[$dateStr] ?? null;

            if ($record && in_array($record->status, ['present', 'late'], true)) {
                // worked — no dock
            } elseif ($record && $record->status === 'half_day') {
                $absence += 0.5;
            } elseif (isset($paidLeaveDates[$dateStr])) {
                // paid leave — no dock
            } elseif (isset($unpaidLeaveDates[$dateStr])) {
                $absence += 1.0;
            } elseif ($holiday) {
                // Holiday on a scheduled work day — covered by the monthly-equivalent
                // basic, so it is not docked even when not worked.
            } else {
                $absence += 1.0;
            }

            $day->addDay();
        }

        return $absence;
    }

    /**
     * Build approved-overtime earning rows grouped by type.
     *
     * @return array<int, array{type: string, amount: float, hours: float, description: string, is_taxable: bool}>
     */
    private function buildOvertimeEarnings(Employee $employee, Carbon $cutoffStart, Carbon $cutoffEnd, float $hourlyRate): array
    {
        /** @var Collection<int, OvertimeRecord> $overtimeRecords */
        $overtimeRecords = OvertimeRecord::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$cutoffStart, $cutoffEnd])
            ->get();

        $otByType = [
            'weekday' => ['pay' => 0.0, 'hours' => 0.0],
            'weekend' => ['pay' => 0.0, 'hours' => 0.0],
            'holiday' => ['pay' => 0.0, 'hours' => 0.0],
        ];

        foreach ($overtimeRecords as $ot) {
            $otPay = round((float) $ot->hours * $hourlyRate * (float) $ot->pay_rate_multiplier, 2);
            $otType = array_key_exists($ot->overtime_type ?? 'weekday', $otByType) ? $ot->overtime_type : 'weekday';

            $otByType[$otType]['pay'] += $otPay;
            $otByType[$otType]['hours'] += (float) $ot->hours;
        }

        $otLabels = [
            'weekday' => 'Weekday overtime (125%)',
            'weekend' => 'Rest day overtime (150%)',
            'holiday' => 'Holiday overtime (200%)',
        ];

        $earnings = [];

        foreach ($otByType as $otType => $data) {
            if ($data['pay'] > 0) {
                $earnings[] = [
                    'type' => 'overtime_'.$otType,
                    'amount' => round($data['pay'], 2),
                    'hours' => round($data['hours'], 2),
                    'description' => $otLabels[$otType],
                    'is_taxable' => true,
                ];
            }
        }

        return $earnings;
    }

    /**
     * Merge national + company holidays across the (possibly month-spanning) cut-off.
     *
     * @return array<string, \App\Modules\Payroll\Models\Holiday>
     */
    private function buildHolidayMap(Carbon $cutoffStart, Carbon $cutoffEnd, int $companyId): array
    {
        $map = $this->holidays->getHolidaysForYear((int) $cutoffStart->year, $companyId);

        if ((int) $cutoffEnd->year !== (int) $cutoffStart->year) {
            $map += $this->holidays->getHolidaysForYear((int) $cutoffEnd->year, $companyId);
        }

        return array_filter(
            $map,
            fn (string $dateStr): bool => $dateStr >= $cutoffStart->toDateString() && $dateStr <= $cutoffEnd->toDateString(),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function resolveMonthlyBasicSalary(Employee $employee, int $workDaysPerMonth): float
    {
        $salary = (float) ($employee->salary ?? 0);

        if ($employee->salary_type === 'daily') {
            return $salary * $workDaysPerMonth;
        }

        return $salary;
    }

    private function isLastCutoffOfMonth(PayrollPeriod $period): bool
    {
        $periodType = $period->setting->period_type;

        if ($periodType === 'monthly') {
            return true;
        }

        if ($periodType === 'weekly') {
            // Treat the week that contains the month's last day as the loan/contribution cut-off.
            return (int) $period->end_date->month !== (int) $period->end_date->copy()->addWeek()->month
                || $period->end_date->isLastOfMonth();
        }

        // Semi-monthly: last cut-off ends on the last day of the month.
        return (int) $period->end_date->day === (int) $period->end_date->copy()->endOfMonth()->day;
    }
}
