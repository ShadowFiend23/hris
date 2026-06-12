<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPayrollJob;
use App\Models\User;
use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Payroll\Models\PayrollSetting;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateHistoricPayroll extends Command
{
    protected $signature = 'payroll:generate-historic
                            {--from=2026-01 : Start month in YYYY-MM format}
                            {--to=2026-03 : End month in YYYY-MM format}';

    protected $description = 'Generate and finalize payroll periods for historical months (e.g. Jan–Mar 2026)';

    public function handle(): int
    {
        $from = Carbon::createFromFormat('Y-m', $this->option('from'))->startOfMonth();
        $to = Carbon::createFromFormat('Y-m', $this->option('to'))->startOfMonth();

        if ($from->gt($to)) {
            $this->error('--from must be before or equal to --to.');

            return self::FAILURE;
        }

        $admin = User::where('email', 'test@example.com')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first();

        if (! $admin) {
            $this->error('No admin user found. Ensure the database is seeded.');

            return self::FAILURE;
        }

        $setting = PayrollSetting::first();

        if (! $setting) {
            $this->error('No PayrollSetting found. Run db:seed --class=PayrollSettingSeeder first.');

            return self::FAILURE;
        }

        $this->info("Generating historic payroll: {$from->format('Y-m')} → {$to->format('Y-m')}");
        $this->info("PayrollSetting: {$setting->period_type}, pay_day_1={$setting->pay_day_1}, pay_day_2={$setting->pay_day_2}");

        $current = $from->copy();

        while ($current->lte($to)) {
            $this->processMonth($current, $setting, $admin->id);
            $current->addMonth();
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function processMonth(Carbon $month, PayrollSetting $setting, int $adminId): void
    {
        $year = $month->year;
        $monthNum = $month->month;
        $payDay1 = $setting->pay_day_1;
        $payDay2 = $setting->pay_day_2 ?? 30;
        $endOfMonth = $month->copy()->endOfMonth()->day;

        $offsetDays = $setting->cutoff_offset_days ?? 15;

        $periods = [
            [
                'start_date' => Carbon::create($year, $monthNum, 1),
                'end_date' => Carbon::create($year, $monthNum, $payDay1),
                'pay_date' => Carbon::create($year, $monthNum, $payDay1),
                'cutoff_start_date' => Carbon::create($year, $monthNum, 1)->subDays($offsetDays),
                'cutoff_end_date' => Carbon::create($year, $monthNum, 1)->subDays(1),
            ],
            [
                'start_date' => Carbon::create($year, $monthNum, $payDay1 + 1),
                'end_date' => Carbon::create($year, $monthNum, $endOfMonth),
                'pay_date' => Carbon::create($year, $monthNum, min($payDay2, $endOfMonth)),
                'cutoff_start_date' => Carbon::create($year, $monthNum, $payDay1 + 1)->subDays($offsetDays),
                'cutoff_end_date' => Carbon::create($year, $monthNum, $payDay1),
            ],
        ];

        foreach ($periods as $periodDates) {
            $label = $periodDates['start_date']->format('M j').'–'.$periodDates['end_date']->format('M j, Y');

            // Skip if a finalized period already exists for this date range
            $existing = PayrollPeriod::where('company_id', $setting->company_id)
                ->where('start_date', $periodDates['start_date']->toDateString())
                ->where('end_date', $periodDates['end_date']->toDateString())
                ->where('status', 'finalized')
                ->first();

            if ($existing) {
                $this->line("  SKIP  {$label} (already finalized, id={$existing->id})");

                continue;
            }

            // Remove any non-finalized duplicate before creating
            PayrollPeriod::where('company_id', $setting->company_id)
                ->where('start_date', $periodDates['start_date']->toDateString())
                ->where('end_date', $periodDates['end_date']->toDateString())
                ->whereIn('status', ['draft', 'review'])
                ->delete();

            $period = PayrollPeriod::create([
                'company_id' => $setting->company_id,
                'payroll_setting_id' => $setting->id,
                'start_date' => $periodDates['start_date'],
                'end_date' => $periodDates['end_date'],
                'cutoff_start_date' => $periodDates['cutoff_start_date'],
                'cutoff_end_date' => $periodDates['cutoff_end_date'],
                'pay_date' => $periodDates['pay_date'],
                'status' => 'draft',
            ]);

            // Recompute late status for attendance records in this period
            $this->recomputeLateStatus($period->start_date, $period->end_date);

            // Process payroll (synchronous because QUEUE_CONNECTION=sync)
            $this->line("  RUN   {$label} (period_id={$period->id})");
            ProcessPayrollJob::dispatch($period->id, $adminId);

            $period->refresh();
            $itemCount = $period->items()->count();
            $this->line("  DONE  {$label}: {$itemCount} payslips, status={$period->status}");
        }
    }

    private function recomputeLateStatus(Carbon $startDate, Carbon $endDate): void
    {
        // Build a map of employee_id => shift start time (Carbon)
        /** @var array<int, Carbon|null> $shiftStartMap */
        $shiftStartMap = Employee::where('is_active', true)
            ->with('shiftTemplate')
            ->get()
            ->mapWithKeys(fn (Employee $emp) => [
                $emp->id => $emp->shiftTemplate?->start_time,
            ])
            ->all();

        $records = AttendanceRecord::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotNull('clock_in')
            ->get();

        foreach ($records as $record) {
            $shiftStart = $shiftStartMap[$record->employee_id] ?? null;

            if (! $shiftStart) {
                continue;
            }

            // Build grace period deadline: shift start + 15 minutes, on the record's date
            $date = Carbon::parse($record->date);
            $deadline = $date->copy()
                ->setHour((int) $shiftStart->format('H'))
                ->setMinute((int) $shiftStart->format('i'))
                ->setSecond(0)
                ->addMinutes(15);

            $clockIn = Carbon::parse($record->clock_in);

            if ($clockIn->gt($deadline) && $record->status === 'present') {
                $record->status = 'late';
                $minutesLate = (int) $deadline->diffInMinutes($clockIn);
                $record->minutes_late = max(0, $minutesLate);
                $record->save();
            }
        }
    }
}
