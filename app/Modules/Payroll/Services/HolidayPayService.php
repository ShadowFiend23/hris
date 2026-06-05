<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\Holiday;
use Carbon\Carbon;

/**
 * Holiday pay per Philippine labor law (Labor Code Art. 94, DO 28-30).
 * Regular holiday:      200% of daily rate (100% if not worked, 200% if worked).
 * Special non-working:  130% of daily rate if worked; no pay if not worked.
 */
class HolidayPayService
{
    /**
     * Get all holidays (national + company) effective on a given year.
     *
     * @return array<string, \App\Modules\Payroll\Models\Holiday> Keyed by date string (Y-m-d)
     */
    public function getHolidaysForYear(int $year, ?int $companyId = null): array
    {
        $query = Holiday::where('is_active', true)
            ->where(function ($q) use ($year): void {
                // Exact year matches
                $q->whereYear('date', $year);
                // Or recurring holidays (match month+day from any year)
                $q->orWhere('is_recurring', true);
            })
            ->where(function ($q) use ($companyId): void {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            });

        $holidays = [];

        foreach ($query->get() as $holiday) {
            $date = $holiday->is_recurring
                ? Carbon::create($year, $holiday->date->month, $holiday->date->day)->toDateString()
                : $holiday->date->toDateString();

            $holidays[$date] = $holiday;
        }

        return $holidays;
    }

    /**
     * Determine if a date is a holiday and return the Holiday model or null.
     */
    public function getHolidayForDate(Carbon $date, ?int $companyId = null): ?Holiday
    {
        $holidays = $this->getHolidaysForYear($date->year, $companyId);

        return $holidays[$date->toDateString()] ?? null;
    }

    /**
     * Compute holiday pay premium for hours worked on a holiday.
     *
     * @param  float  $dailyRate  Employee's daily rate
     * @param  string  $holidayType  'regular' or 'special'
     * @param  float  $hoursWorked  Hours worked on that day
     * @param  float  $standardHours  Standard work hours per day (default 8)
     * @return float The total amount payable for that holiday day
     */
    public function computeHolidayPay(
        float $dailyRate,
        string $holidayType,
        float $hoursWorked,
        float $standardHours = 8.0
    ): float {
        $hourlyRate = $dailyRate / $standardHours;

        if ($holidayType === 'regular') {
            // 200% for actual hours worked (base 100% + 100% premium)
            return round($hourlyRate * $hoursWorked * 2.0, 2);
        }

        // Special non-working: 130% if worked, 0 if not worked
        if ($hoursWorked > 0) {
            return round($hourlyRate * $hoursWorked * 1.30, 2);
        }

        return 0.0;
    }
}
