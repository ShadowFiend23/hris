<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\ContributionBracket;
use Illuminate\Support\Collection;

/**
 * Withholding tax per BIR / TRAIN Law (RA 10963).
 *
 * Two representations are stored in contribution_brackets (type='tax'):
 *   - period = null            → annualized brackets (used for year-end / reference)
 *   - period = semi_monthly|monthly|weekly → BIR revised withholding tax tables,
 *     withheld each payroll run on that cut-off's net taxable compensation.
 *
 * For each row: min_salary = bracket floor, employee_amount = fixed base tax,
 * employee_rate = marginal rate on the excess over the floor.
 */
class WithholdingTaxService
{
    /**
     * @var array<string, int>
     */
    private const PERIODS_PER_YEAR = [
        'semi_monthly' => 24,
        'monthly' => 12,
        'weekly' => 52,
    ];

    /**
     * Withhold tax for a single payroll period using the BIR table for that period type.
     */
    public function computeForPeriod(float $taxableIncome, string $periodType): float
    {
        if ($taxableIncome <= 0) {
            return 0.0;
        }

        $bracket = ContributionBracket::where('type', 'tax')
            ->where('period', $periodType)
            ->where('is_active', true)
            ->where('min_salary', '<=', $taxableIncome)
            ->orderByDesc('min_salary')
            ->first();

        if ($bracket) {
            return round(
                (float) $bracket->employee_amount
                    + (($taxableIncome - (float) $bracket->min_salary) * (float) $bracket->employee_rate),
                2
            );
        }

        // Fallback when no per-period table is seeded: annualize and divide back.
        $periods = self::PERIODS_PER_YEAR[$periodType] ?? 12;

        return round($this->computeAnnualTax($taxableIncome * $periods) / $periods, 2);
    }

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
            ->whereNull('period')
            ->where('is_active', true)
            ->orderBy('min_salary')
            ->get();
    }

    private function findBracket(float $income): ?ContributionBracket
    {
        return ContributionBracket::where('type', 'tax')
            ->whereNull('period')
            ->where('is_active', true)
            ->where('min_salary', '<=', $income)
            ->orderByDesc('min_salary')
            ->first();
    }
}
