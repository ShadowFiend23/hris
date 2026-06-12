<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\Holiday;
use Carbon\Carbon;

/**
 * Holiday pay per Philippine labor law (Labor Code Art. 94, DO 28-30).
 * Regular holiday:      200% of daily rate when worked, 100% when not worked.
 * Special non-working:  130% of daily rate when worked, "no work, no pay" otherwise.
 *
 * For monthly-paid employees the base day is already covered by the monthly salary,
 * so only the *premium* portion is added on top of basic.
 */
class HolidayPayService
{
    /**
     * Get all holidays (national + company) effective on a given year, de-duplicated
     * so each calendar date resolves to exactly one Holiday. When both an exact-year
     * row and a recurring row land on the same date, the exact-year row wins; a
     * `regular` classification is preferred over `special` as a final tiebreak so a
     * mistyped duplicate can never silently downgrade a regular holiday.
     *
     * @return array<string, \App\Modules\Payroll\Models\Holiday> Keyed by date string (Y-m-d)
     */
    public function getHolidaysForYear(int $year, ?int $companyId = null): array
    {
        $query = Holiday::where('is_active', true)
            ->where(function ($q) use ($year): void {
                $q->whereYear('date', $year);
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

            if (! isset($holidays[$date])) {
                $holidays[$date] = $holiday;

                continue;
            }

            $holidays[$date] = $this->preferHoliday($holidays[$date], $holiday, $year);
        }

        return $holidays;
    }

    /**
     * Decide which of two colliding holidays should represent a date.
     */
    private function preferHoliday(Holiday $current, Holiday $candidate, int $year): Holiday
    {
        $currentExact = (int) $current->date->year === $year && ! $current->is_recurring;
        $candidateExact = (int) $candidate->date->year === $year && ! $candidate->is_recurring;

        if ($candidateExact !== $currentExact) {
            return $candidateExact ? $candidate : $current;
        }

        // Same specificity: prefer the regular classification.
        if ($candidate->isRegular() && ! $current->isRegular()) {
            return $candidate;
        }

        return $current;
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
     * Compute the holiday *premium* earning to add for hours worked on a holiday.
     *
     * Basic pay in this system is a monthly-equivalent figure that already covers
     * the base day (the day is never docked when it falls on a holiday), so only the
     * premium over the base 100% is added on top:
     *   - Regular holiday worked  → +100% (regularRate − 1).
     *   - Special holiday worked   → +30%  (specialRate − 1).
     * A holiday that is not worked adds nothing extra (the base day stays in basic).
     *
     * @param  float  $dailyRate  Employee's daily rate
     * @param  string  $holidayType  'regular' or 'special'
     * @param  float  $hoursWorked  Hours worked on that day (0 if not worked)
     * @param  float  $standardHours  Standard work hours per day
     * @param  float  $regularRate  Regular-holiday multiplier (e.g. 2.00)
     * @param  float  $specialRate  Special-holiday multiplier (e.g. 1.30)
     */
    public function computeHolidayEarning(
        float $dailyRate,
        string $holidayType,
        float $hoursWorked,
        float $standardHours,
        float $regularRate = 2.0,
        float $specialRate = 1.30
    ): float {
        if ($hoursWorked <= 0) {
            return 0.0;
        }

        $hourlyRate = $standardHours > 0 ? $dailyRate / $standardHours : 0.0;
        $premiumMultiplier = ($holidayType === 'regular' ? $regularRate : $specialRate) - 1.0;

        return round($hourlyRate * $hoursWorked * $premiumMultiplier, 2);
    }

    /**
     * Compute the earning for hours worked on a rest day (Labor Code Art. 93).
     *
     * A rest day is outside the employee's scheduled days, so it is not covered by basic
     * pay — the full statutory rate is paid (not just the premium):
     *   - plain rest day            → 130% (rest_day_rate)
     *   - rest day + regular holiday → 260% (regular_holiday_rate × rest_day_rate)
     *   - rest day + special holiday → 150% (DOLE)
     *
     * @param  string|null  $holidayType  'regular', 'special', or null when not a holiday
     */
    public function computeRestDayEarning(
        float $dailyRate,
        float $hoursWorked,
        float $standardHours,
        ?string $holidayType = null,
        float $restRate = 1.30,
        float $regularRate = 2.0
    ): float {
        if ($hoursWorked <= 0) {
            return 0.0;
        }

        $hourlyRate = $standardHours > 0 ? $dailyRate / $standardHours : 0.0;

        $rate = match ($holidayType) {
            'regular' => $regularRate * $restRate, // e.g. 2.00 × 1.30 = 260%
            'special' => 1.50,                     // special non-working + rest day (DOLE)
            default => $restRate,                  // plain rest day = 130%
        };

        return round($hourlyRate * $hoursWorked * $rate, 2);
    }
}
