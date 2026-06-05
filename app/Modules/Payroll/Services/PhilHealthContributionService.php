<?php

namespace App\Modules\Payroll\Services;

/**
 * PhilHealth contribution per 2023 UHC Law.
 * Premium rate: 5% of monthly basic salary.
 * Shared equally: 2.5% employee, 2.5% employer.
 * Monthly cap: ₱5,000 total (₱2,500 each side).
 * Monthly floor: ₱500 total (₱250 each side), applies when salary < ₱10,000.
 */
class PhilHealthContributionService
{
    private const RATE = 0.05;

    private const MIN_MONTHLY = 500.0;

    private const MAX_MONTHLY = 5000.0;

    public function computeEmployeeShare(float $monthlyBasicPay): float
    {
        return round($this->computeTotalMonthlyPremium($monthlyBasicPay) / 2, 2);
    }

    public function computeEmployerShare(float $monthlyBasicPay): float
    {
        return round($this->computeTotalMonthlyPremium($monthlyBasicPay) / 2, 2);
    }

    public function computeTotalMonthlyPremium(float $monthlyBasicPay): float
    {
        $computed = $monthlyBasicPay * self::RATE;

        return min(max($computed, self::MIN_MONTHLY), self::MAX_MONTHLY);
    }

    /**
     * For semi-monthly payroll, deduct half the monthly contribution each cut-off.
     */
    public function computeEmployeeSharePerCutoff(float $monthlyBasicPay, int $cutoffsPerMonth = 2): float
    {
        return round($this->computeEmployeeShare($monthlyBasicPay) / $cutoffsPerMonth, 2);
    }
}
