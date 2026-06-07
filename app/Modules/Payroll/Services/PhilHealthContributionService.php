<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\ContributionBracket;

class PhilHealthContributionService
{
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
        $setting = $this->getSetting();

        $rate = $setting ? ((float) $setting->employee_rate + (float) $setting->employer_rate) : 0.05;
        $min = $setting ? ((float) ($setting->min_contribution ?? 250) * 2) : 500.0;
        $max = $setting ? ((float) ($setting->max_contribution ?? 2500) * 2) : 5000.0;

        $computed = $monthlyBasicPay * $rate;

        return min(max($computed, $min), $max);
    }

    public function computeEmployeeSharePerCutoff(float $monthlyBasicPay, int $cutoffsPerMonth = 2): float
    {
        return round($this->computeEmployeeShare($monthlyBasicPay) / $cutoffsPerMonth, 2);
    }

    public function getSetting(): ?ContributionBracket
    {
        return ContributionBracket::where('type', 'philhealth')
            ->where('is_active', true)
            ->orderByDesc('effective_date')
            ->first();
    }
}
