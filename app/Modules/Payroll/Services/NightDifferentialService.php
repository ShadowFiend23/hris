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
     * @param  float  $rate  Night differential rate (e.g. 0.10)
     * @param  int  $breakMinutes  Unpaid break minutes to exclude from the night band
     * @param  float|null  $workedHoursCap  Actual hours worked; ND hours never exceed this
     */
    public function compute(
        Carbon $clockIn,
        Carbon $clockOut,
        float $hourlyRate,
        float $rate = self::ND_RATE,
        int $breakMinutes = 0,
        ?float $workedHoursCap = null
    ): float {
        $ndHours = $this->computeNightHours($clockIn, $clockOut, $breakMinutes, $workedHoursCap);

        return round($ndHours * $hourlyRate * $rate, 2);
    }

    /**
     * Calculate total hours worked within the 10PM–6AM window, net of any break
     * time that falls inside that window, and never exceeding hours actually worked.
     */
    public function computeNightHours(
        Carbon $clockIn,
        Carbon $clockOut,
        int $breakMinutes = 0,
        ?float $workedHoursCap = null
    ): float {
        if ($clockOut->lte($clockIn)) {
            return 0.0;
        }

        $ndSeconds = $this->intersectWithNightBand($clockIn, $clockOut);
        $ndHours = $ndSeconds / 3600;

        // Exclude the share of the unpaid break that falls within the night band.
        // Without an explicit break window we attribute the break proportionally to
        // the fraction of the shift that lies inside the night band.
        if ($breakMinutes > 0 && $ndHours > 0) {
            $totalShiftSeconds = max(1, $clockOut->getTimestamp() - $clockIn->getTimestamp());
            $nightFraction = min(1.0, $ndSeconds / $totalShiftSeconds);
            $ndHours -= ($breakMinutes / 60) * $nightFraction;
        }

        $ndHours = max(0.0, $ndHours);

        if ($workedHoursCap !== null) {
            $ndHours = min($ndHours, max(0.0, $workedHoursCap));
        }

        return round($ndHours, 4);
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
