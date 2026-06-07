<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\ContributionBracket;
use Illuminate\Support\Collection;

class PagibigContributionService
{
    public function computeEmployeeShare(float $monthlyBasicPay): float
    {
        $bracket = $this->findBracket($monthlyBasicPay);

        if (! $bracket) {
            return 0.0;
        }

        $computed = $monthlyBasicPay * (float) $bracket->employee_rate;
        $max = $bracket->max_contribution !== null ? (float) $bracket->max_contribution : 200.0;

        return min($computed, $max);
    }

    public function computeEmployerShare(float $monthlyBasicPay): float
    {
        $bracket = $this->findBracket($monthlyBasicPay);

        if (! $bracket) {
            return 0.0;
        }

        return round($monthlyBasicPay * (float) $bracket->employer_rate, 2);
    }

    public function computeEmployeeSharePerCutoff(float $monthlyBasicPay, int $cutoffsPerMonth = 2): float
    {
        return round($this->computeEmployeeShare($monthlyBasicPay) / $cutoffsPerMonth, 2);
    }

    public function getBrackets(): Collection
    {
        return ContributionBracket::where('type', 'pagibig')
            ->where('is_active', true)
            ->orderBy('min_salary')
            ->get();
    }

    private function findBracket(float $salary): ?ContributionBracket
    {
        return ContributionBracket::where('type', 'pagibig')
            ->where('is_active', true)
            ->where('min_salary', '<=', $salary)
            ->where(function ($q) use ($salary): void {
                $q->whereNull('max_salary')->orWhere('max_salary', '>=', $salary);
            })
            ->orderByDesc('min_salary')
            ->first();
    }
}
