<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\PayrollSetting;
use Carbon\Carbon;

class PayrollPeriodService
{
    /**
     * Generate payroll period dates for a given setting and reference date.
     *
     * @return array{start_date: Carbon, end_date: Carbon, pay_date: Carbon}
     */
    public function generateNextPeriod(PayrollSetting $setting, Carbon $referenceDate): array
    {
        return match ($setting->period_type) {
            'monthly' => $this->generateMonthlyPeriod($referenceDate),
            'weekly' => $this->generateWeeklyPeriod($referenceDate),
            default => $this->generateSemiMonthlyPeriod($setting, $referenceDate),
        };
    }

    /**
     * @return array{start_date: Carbon, end_date: Carbon, pay_date: Carbon}
     */
    private function generateSemiMonthlyPeriod(PayrollSetting $setting, Carbon $referenceDate): array
    {
        $day = (int) $referenceDate->day;
        $payDay1 = (int) $setting->pay_day_1;
        $payDay2 = (int) ($setting->pay_day_2 ?? 30);
        $year = (int) $referenceDate->year;
        $month = (int) $referenceDate->month;

        if ($day <= $payDay1) {
            // First cut-off: 1st to pay_day_1
            return [
                'start_date' => Carbon::create($year, $month, 1),
                'end_date' => Carbon::create($year, $month, $payDay1),
                'pay_date' => Carbon::create($year, $month, $payDay1),
            ];
        }

        // Second cut-off: pay_day_1+1 to end of month
        $endOfMonth = Carbon::create($year, $month)->endOfMonth()->day;

        return [
            'start_date' => Carbon::create($year, $month, $payDay1 + 1),
            'end_date' => Carbon::create($year, $month, $endOfMonth),
            'pay_date' => Carbon::create($year, $month, min($payDay2, $endOfMonth)),
        ];
    }

    /**
     * @return array{start_date: Carbon, end_date: Carbon, pay_date: Carbon}
     */
    private function generateMonthlyPeriod(Carbon $referenceDate): array
    {
        $start = $referenceDate->copy()->startOfMonth();
        $end = $referenceDate->copy()->endOfMonth();

        return [
            'start_date' => $start,
            'end_date' => $end,
            'pay_date' => $end,
        ];
    }

    /**
     * @return array{start_date: Carbon, end_date: Carbon, pay_date: Carbon}
     */
    private function generateWeeklyPeriod(Carbon $referenceDate): array
    {
        $start = $referenceDate->copy()->startOfWeek(Carbon::MONDAY);
        $end = $referenceDate->copy()->endOfWeek(Carbon::SUNDAY);

        return [
            'start_date' => $start,
            'end_date' => $end,
            'pay_date' => $end->copy()->addDays(2),
        ];
    }

    /**
     * Determine how many cut-offs per month for a period type.
     */
    public function cutoffsPerMonth(string $periodType): int
    {
        return match ($periodType) {
            'weekly' => 4,
            'semi_monthly' => 2,
            default => 1,
        };
    }
}
