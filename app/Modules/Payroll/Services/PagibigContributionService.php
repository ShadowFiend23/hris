<?php

namespace App\Modules\Payroll\Services;

/**
 * Pag-IBIG (HDMF) contribution.
 * Employee: 1% if salary ≤ ₱1,500; 2% if salary > ₱1,500. Max ₱200/month.
 * Employer: 2% of monthly basic salary.
 */
class PagibigContributionService
{
    private const EMPLOYEE_MAX = 200.0;

    private const EMPLOYER_RATE = 0.02;

    public function computeEmployeeShare(float $monthlyBasicPay): float
    {
        $rate = $monthlyBasicPay <= 1500.0 ? 0.01 : 0.02;
        $computed = $monthlyBasicPay * $rate;

        return min($computed, self::EMPLOYEE_MAX);
    }

    public function computeEmployerShare(float $monthlyBasicPay): float
    {
        return round($monthlyBasicPay * self::EMPLOYER_RATE, 2);
    }

    /**
     * For semi-monthly payroll, deduct half the monthly contribution each cut-off.
     */
    public function computeEmployeeSharePerCutoff(float $monthlyBasicPay, int $cutoffsPerMonth = 2): float
    {
        return round($this->computeEmployeeShare($monthlyBasicPay) / $cutoffsPerMonth, 2);
    }
}
