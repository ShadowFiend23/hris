<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\ContributionBracket;
use Illuminate\Support\Collection;

/**
 * Withholding tax via the annualized method per TRAIN Law (RA 10963).
 * Brackets are stored in contribution_brackets (type='tax'):
 *   min_salary    = minimum annual income for this bracket
 *   employee_amount = fixed base tax
 *   employee_rate   = marginal rate on excess
 */
class WithholdingTaxService
{
    public function computeMonthlyWithholding(float $monthlyTaxableIncome, int $monthsRemaining = 12): float
    {
        $annualizedIncome = $monthlyTaxableIncome * $monthsRemaining;
        $annualTax = $this->computeAnnualTax($annualizedIncome);

        return round($annualTax / $monthsRemaining, 2);
    }

    public function computeAnnualTax(float $annualTaxableIncome): float
    {
        if ($annualTaxableIncome <= 0) {
            return 0.0;
        }

        $bracket = $this->findBracket($annualTaxableIncome);

        if (! $bracket) {
            return 0.0;
        }

        $baseTax = (float) $bracket->employee_amount;
        $rate = (float) $bracket->employee_rate;
        $minIncome = (float) $bracket->min_salary;

        return round($baseTax + (($annualTaxableIncome - $minIncome) * $rate), 2);
    }

    public function getBrackets(): Collection
    {
        return ContributionBracket::where('type', 'tax')
            ->where('is_active', true)
            ->orderBy('min_salary')
            ->get();
    }

    private function findBracket(float $income): ?ContributionBracket
    {
        return ContributionBracket::where('type', 'tax')
            ->where('is_active', true)
            ->where('min_salary', '<=', $income)
            ->orderByDesc('min_salary')
            ->first();
    }
}
