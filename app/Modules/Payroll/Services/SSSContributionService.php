<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\ContributionBracket;

/**
 * SSS contribution based on 2025 SSS contribution table.
 * Employee share: 4.5%, Employer share: 9.5%, EC: ₱10–₱30.
 * Lookup is bracket-based from contribution_brackets table.
 */
class SSSContributionService
{
    public function computeEmployeeShare(float $monthlyBasicPay): float
    {
        $bracket = $this->findBracket($monthlyBasicPay);

        if (! $bracket) {
            return 0.0;
        }

        if ($bracket->employee_amount !== null) {
            return (float) $bracket->employee_amount;
        }

        if ($bracket->employee_rate !== null) {
            return round($monthlyBasicPay * (float) $bracket->employee_rate, 2);
        }

        return 0.0;
    }

    public function computeEmployerShare(float $monthlyBasicPay): float
    {
        $bracket = $this->findBracket($monthlyBasicPay);

        if (! $bracket) {
            return 0.0;
        }

        if ($bracket->employer_amount !== null) {
            return (float) $bracket->employer_amount;
        }

        if ($bracket->employer_rate !== null) {
            return round($monthlyBasicPay * (float) $bracket->employer_rate, 2);
        }

        return 0.0;
    }

    private function findBracket(float $salary): ?ContributionBracket
    {
        return ContributionBracket::where('type', 'sss')
            ->where('is_active', true)
            ->where('min_salary', '<=', $salary)
            ->where(function ($q) use ($salary): void {
                $q->whereNull('max_salary')->orWhere('max_salary', '>=', $salary);
            })
            ->orderByDesc('min_salary')
            ->first();
    }
}
