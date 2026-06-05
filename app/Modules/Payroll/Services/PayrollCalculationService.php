<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\EmployeeAllowance;
use App\Modules\Payroll\Models\Loan;
use App\Modules\Payroll\Models\PayrollDeduction;
use App\Modules\Payroll\Models\PayrollEarning;
use App\Modules\Payroll\Models\PayrollItem;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use App\Modules\Timekeeping\Models\WorkPolicy;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
    ) {}

    /**
     * Compute and persist a PayrollItem for a single employee in a given period.
     */
    public function computeForEmployee(PayrollPeriod $period, Employee $employee): PayrollItem
    {
        // Remove any existing draft item for this period+employee
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

        $cutoffStart = $period->cutoff_start_date ?? $period->start_date;
        $cutoffEnd = $period->cutoff_end_date ?? $period->end_date;

        /** @var Collection<int, AttendanceRecord> $attendanceRecords */
        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$cutoffStart, $cutoffEnd])
            ->whereNull('deleted_at')
            ->get();

        $totalHours = $attendanceRecords->sum('total_hours');
        $daysPresent = $attendanceRecords->whereIn('status', ['present', 'half_day'])->count();
        $halfDays = $attendanceRecords->where('status', 'half_day')->count();
        $minutesLate = 0; // Can be derived from attendance if clock_in vs shift start is tracked

        $workingDaysInPeriod = $this->countWorkingDays($cutoffStart, $cutoffEnd);
        $daysAbsent = max(0, $workingDaysInPeriod - $daysPresent);

        // --- EARNINGS ---
        $earnings = [];

        // Basic pay (adjusted for absences and half-days)
        $absenceDeductionDays = $daysAbsent + ($halfDays * 0.5);
        $basicPay = max(0, $cutoffBasicPay - ($absenceDeductionDays * ($dailyRate)));

        $earnings[] = ['type' => 'basic', 'amount' => round($basicPay, 2), 'hours' => null, 'description' => 'Basic pay', 'is_taxable' => true];

        // Overtime — split by type (weekday / weekend / holiday)
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
            $otType = $ot->overtime_type ?? 'weekday';

            if (! array_key_exists($otType, $otByType)) {
                $otType = 'weekday';
            }

            $otByType[$otType]['pay'] += $otPay;
            $otByType[$otType]['hours'] += (float) $ot->hours;
        }

        $otLabels = [
            'weekday' => 'Weekday overtime (125%)',
            'weekend' => 'Rest day overtime (150%)',
            'holiday' => 'Holiday overtime (200%)',
        ];

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

        // Night differential & holiday pay
        $holidayMap = $this->holidays->getHolidaysForYear((int) $cutoffStart->year, $employee->company_id);
        $totalNightDiff = 0.0;
        $totalNightDiffHours = 0.0;
        $totalRegularHolidayPay = 0.0;
        $totalSpecialHolidayPay = 0.0;

        foreach ($attendanceRecords as $record) {
            if (! $record->clock_in || ! $record->clock_out) {
                continue;
            }

            $dateStr = $record->date->toDateString();

            // Night differential
            $ndRate = (float) ($period->setting->night_differential_rate ?? 0.10);
            $ndPay = $this->nightDiff->compute($record->clock_in, $record->clock_out, $hourlyRate, $ndRate);
            $totalNightDiff += $ndPay;
            $totalNightDiffHours += $this->nightDiff->computeNightHours($record->clock_in, $record->clock_out);

            // Holiday premium
            if (isset($holidayMap[$dateStr])) {
                $holiday = $holidayMap[$dateStr];
                $hoursWorked = (float) $record->total_hours;
                $holidayPay = $this->holidays->computeHolidayPay($dailyRate, $holiday->type, $hoursWorked, $standardHoursPerDay);

                if ($holiday->isRegular()) {
                    $totalRegularHolidayPay += $holidayPay;
                } else {
                    $totalSpecialHolidayPay += $holidayPay;
                }
            }
        }

        if ($totalNightDiff > 0) {
            $ndRatePercent = round(($period->setting->night_differential_rate ?? 0.10) * 100);
            $earnings[] = ['type' => 'night_differential', 'amount' => round($totalNightDiff, 2), 'hours' => round($totalNightDiffHours, 2), 'description' => "Night shift differential ({$ndRatePercent}%)", 'is_taxable' => true];
        }

        if ($totalRegularHolidayPay > 0) {
            $regularHolidayHours = $attendanceRecords->filter(function ($r) use ($holidayMap): bool {
                return isset($holidayMap[$r->date->toDateString()]) && $holidayMap[$r->date->toDateString()]->isRegular();
            })->sum('total_hours');
            $earnings[] = ['type' => 'regular_holiday', 'amount' => round($totalRegularHolidayPay, 2), 'hours' => round((float) $regularHolidayHours, 2), 'description' => 'Regular holiday pay (200%)', 'is_taxable' => true];
        }

        if ($totalSpecialHolidayPay > 0) {
            $specialHolidayHours = $attendanceRecords->filter(function ($r) use ($holidayMap): bool {
                return isset($holidayMap[$r->date->toDateString()]) && ! $holidayMap[$r->date->toDateString()]->isRegular();
            })->sum('total_hours');
            $earnings[] = ['type' => 'special_holiday', 'amount' => round($totalSpecialHolidayPay, 2), 'hours' => round((float) $specialHolidayHours, 2), 'description' => 'Special holiday pay (130%)', 'is_taxable' => true];
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

        // Government mandatory contributions (based on full monthly salary, split by cutoffs)
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

        // Withholding tax (only deduct once per month — on the 2nd cutoff for semi-monthly, or each period for monthly/weekly)
        $isLastCutoffOfMonth = $this->isLastCutoffOfMonth($period);

        if ($isLastCutoffOfMonth) {
            // Monthly taxable earnings: taxable items × cutoffs (proxy for full-month) minus mandatory contributions
            $monthlyTaxableEarnings = collect($earnings)
                ->where('is_taxable', true)
                ->sum('amount') * $cutoffs;
            $taxableIncome = $monthlyTaxableEarnings
                - ($sssContrib * $cutoffs)
                - ($philhealthContrib * $cutoffs)
                - ($pagibigContrib * $cutoffs);
            $monthsRemaining = 13 - (int) $period->pay_date->month;
            $monthlyTax = $this->tax->computeMonthlyWithholding(max(0, $taxableIncome), max(1, $monthsRemaining));

            if ($monthlyTax > 0) {
                $deductions[] = ['type' => 'withholding_tax', 'amount' => $monthlyTax, 'description' => 'Withholding tax (TRAIN Law)'];
            }
        }

        // Loan amortizations
        $activeLoans = Loan::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeLoans as $loan) {
            // Deduct monthly amortization on last cutoff of month (or each period for monthly)
            if ($isLastCutoffOfMonth) {
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

        // Absence deduction (already factored into basic pay above, but record for transparency)
        if ($absenceDeductionDays > 0) {
            $absenceAmount = round($absenceDeductionDays * $dailyRate, 2);
            $deductions[] = ['type' => 'absence', 'amount' => $absenceAmount, 'description' => "Absence: {$absenceDeductionDays} day(s)"];
        }

        $totalDeductions = collect($deductions)->sum('amount');
        $netPay = max(0, $grossPay - $totalDeductions);

        // --- PERSIST ---
        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'basic_pay' => $basicPay,
            'gross_pay' => round($grossPay, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => round($netPay, 2),
            'total_hours' => round((float) $totalHours, 2),
            'days_worked' => $daysPresent,
            'days_absent' => $absenceDeductionDays,
            'minutes_late' => $minutesLate,
            'status' => 'draft',
        ]);

        foreach ($earnings as $earning) {
            PayrollEarning::create(array_merge(['payroll_item_id' => $item->id], $earning));
        }

        foreach ($deductions as $deduction) {
            PayrollDeduction::create(array_merge(['payroll_item_id' => $item->id], $deduction));
        }

        // Update loan balances after deduction
        foreach ($activeLoans as $loan) {
            if ($isLastCutoffOfMonth) {
                $amortization = min((float) $loan->monthly_amortization, (float) $loan->balance);
                $newBalance = (float) $loan->balance - $amortization;
                $loan->update([
                    'balance' => max(0, $newBalance),
                    'status' => $newBalance <= 0 ? 'completed' : 'active',
                ]);
            }
        }

        return $item;
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

        $period->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    private function resolveMonthlyBasicSalary(Employee $employee, int $workDaysPerMonth): float
    {
        $salary = (float) ($employee->salary ?? 0);

        if ($employee->salary_type === 'daily') {
            return $salary * $workDaysPerMonth;
        }

        return $salary;
    }

    private function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $days = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (! $current->isWeekend()) {
                $days++;
            }

            $current->addDay();
        }

        return $days;
    }

    private function isLastCutoffOfMonth(PayrollPeriod $period): bool
    {
        $periodType = $period->setting->period_type;

        if ($periodType === 'monthly' || $periodType === 'weekly') {
            return true;
        }

        // Semi-monthly: last cutoff is the one ending at end of month
        return (int) $period->end_date->day === (int) $period->end_date->endOfMonth()->day;
    }
}
