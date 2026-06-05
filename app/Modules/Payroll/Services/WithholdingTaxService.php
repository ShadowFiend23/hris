<?php

namespace App\Modules\Payroll\Services;

/**
 * Withholding tax via the annualized method per TRAIN Law (RA 10963).
 * 2023 annual tax table (BIR RR 8-2018 as amended):
 *   0        – 250,000   : 0%
 *   250,001  – 400,000   : 15% of excess over ₱250,000
 *   400,001  – 800,000   : ₱22,500 + 20% of excess over ₱400,000
 *   800,001  – 2,000,000 : ₱102,500 + 25% of excess over ₱800,000
 *   2,000,001– 8,000,000 : ₱402,500 + 30% of excess over ₱2,000,000
 *   8,000,001+           : ₱2,202,500 + 35% of excess over ₱8,000,000
 */
class WithholdingTaxService
{
    /**
     * @var array<int, array{min: float, base_tax: float, rate: float}>
     */
    private array $brackets = [
        ['min' => 0, 'base_tax' => 0, 'rate' => 0],
        ['min' => 250_000, 'base_tax' => 0, 'rate' => 0.15],
        ['min' => 400_000, 'base_tax' => 22_500, 'rate' => 0.20],
        ['min' => 800_000, 'base_tax' => 102_500, 'rate' => 0.25],
        ['min' => 2_000_000, 'base_tax' => 402_500, 'rate' => 0.30],
        ['min' => 8_000_000, 'base_tax' => 2_202_500, 'rate' => 0.35],
    ];

    /**
     * Compute monthly withholding tax using the annualized method.
     *
     * @param  float  $monthlyTaxableIncome  Gross taxable compensation for the month
     *                                       (basic + overtime + other taxable earnings - non-taxable deductions)
     * @param  int  $monthsRemaining  Months remaining in the year including current month (default 12 for Jan)
     */
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

        return round(
            $bracket['base_tax'] + (($annualTaxableIncome - $bracket['min']) * $bracket['rate']),
            2
        );
    }

    /**
     * @return array{min: float, base_tax: float, rate: float}
     */
    private function findBracket(float $income): array
    {
        $applicable = $this->brackets[0];

        foreach ($this->brackets as $bracket) {
            if ($income >= $bracket['min']) {
                $applicable = $bracket;
            }
        }

        return $applicable;
    }
}
