<?php

namespace App\Modules\Payroll\Services;

use Carbon\Carbon;

/**
 * Night differential per Philippine labor law (Labor Code Art. 86).
 * Night shift differential: additional 10% of regular hourly rate
 * for every hour of work between 10:00 PM and 6:00 AM.
 */
class NightDifferentialService
{
    private const ND_START_HOUR = 22; // 10 PM

    private const ND_END_HOUR = 6;   // 6 AM

    private const ND_RATE = 0.10;

    /**
     * Compute night differential pay given clock-in and clock-out times.
     *
     * @param  float  $hourlyRate  Employee's regular hourly rate
     */
    public function compute(Carbon $clockIn, Carbon $clockOut, float $hourlyRate, float $rate = self::ND_RATE): float
    {
        $ndHours = $this->computeNightHours($clockIn, $clockOut);

        return round($ndHours * $hourlyRate * $rate, 2);
    }

    /**
     * Calculate total hours worked within the 10PM–6AM window.
     */
    public function computeNightHours(Carbon $clockIn, Carbon $clockOut): float
    {
        if ($clockOut->lte($clockIn)) {
            return 0.0;
        }

        $current = $clockIn->copy();
        $ndSeconds = 0;

        // Iterate in 1-minute increments is expensive; use range math instead
        // Split the shift into windows and intersect with 10PM–6AM bands
        $ndSeconds = $this->intersectWithNightBand($clockIn, $clockOut);

        return round($ndSeconds / 3600, 4);
    }

    /**
     * Compute seconds inside the 10PM–6AM band for a given time range.
     * Handles overnight shifts correctly.
     */
    private function intersectWithNightBand(Carbon $start, Carbon $end): int
    {
        $total = 0;

        // Walk day by day
        $day = $start->copy()->startOfDay();

        while ($day->lte($end)) {
            $bandStart = $day->copy()->setHour(self::ND_START_HOUR)->setMinute(0)->setSecond(0);
            $bandEnd = $day->copy()->addDay()->setHour(self::ND_END_HOUR)->setMinute(0)->setSecond(0);

            $intersectStart = max($start->timestamp, $bandStart->timestamp);
            $intersectEnd = min($end->timestamp, $bandEnd->timestamp);

            if ($intersectEnd > $intersectStart) {
                $total += $intersectEnd - $intersectStart;
            }

            $day->addDay();
        }

        return $total;
    }
}
