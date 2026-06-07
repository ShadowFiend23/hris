<?php

namespace Database\Seeders;

use App\Modules\Payroll\Models\ContributionBracket;
use Illuminate\Database\Seeder;

class ContributionBracketsSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->toDateString();

        // SSS — Circular No. 2024-006, effective January 2025
        // employee_amount = Regular SS employee + MPF employee
        // employer_amount = Regular SS employer + MPF employer + EC
        if (! ContributionBracket::where('type', 'sss')->where('is_active', true)->exists()) {
            $note = 'SSS Circular No. 2024-006, effective January 2025';

            $sssBrackets = [
                [0, 5249.99, 250, 510],
                [5250, 5749.99, 275, 560],
                [5750, 6249.99, 300, 610],
                [6250, 6749.99, 325, 660],
                [6750, 7249.99, 350, 710],
                [7250, 7749.99, 375, 760],
                [7750, 8249.99, 400, 810],
                [8250, 8749.99, 425, 860],
                [8750, 9249.99, 450, 910],
                [9250, 9749.99, 475, 960],
                [9750, 10249.99, 500, 1010],
                [10250, 10749.99, 525, 1060],
                [10750, 11249.99, 550, 1110],
                [11250, 11749.99, 575, 1160],
                [11750, 12249.99, 600, 1210],
                [12250, 12749.99, 625, 1260],
                [12750, 13249.99, 650, 1310],
                [13250, 13749.99, 675, 1360],
                [13750, 14249.99, 700, 1410],
                [14250, 14749.99, 725, 1460],
                [14750, 15249.99, 750, 1530],  // EC increases from ₱10 to ₱30 at MSC ≥ 15,000
                [15250, 15749.99, 775, 1580],
                [15750, 16249.99, 800, 1630],
                [16250, 16749.99, 825, 1680],
                [16750, 17249.99, 850, 1730],
                [17250, 17749.99, 875, 1780],
                [17750, 18249.99, 900, 1830],
                [18250, 18749.99, 925, 1880],
                [18750, 19249.99, 950, 1930],
                [19250, 19749.99, 975, 1980],
                [19750, 20249.99, 1000, 2030],
                // MPF kicks in for salary > ₱20,249.99
                [20250, 20749.99, 1025, 2080],
                [20750, 21249.99, 1050, 2130],
                [21250, 21749.99, 1075, 2180],
                [21750, 22249.99, 1100, 2230],
                [22250, 22749.99, 1125, 2280],
                [22750, 23249.99, 1150, 2330],
                [23250, 23749.99, 1175, 2380],
                [23750, 24249.99, 1200, 2430],
                [24250, 24749.99, 1225, 2480],
                [24750, 25249.99, 1250, 2530],
                [25250, 25749.99, 1275, 2580],
                [25750, 26249.99, 1300, 2630],
                [26250, 26749.99, 1325, 2680],
                [26750, 27249.99, 1350, 2730],
                [27250, 27749.99, 1375, 2780],
                [27750, 28249.99, 1400, 2830],
                [28250, 28749.99, 1425, 2880],
                [28750, 29249.99, 1450, 2930],
                [29250, 29749.99, 1475, 2980],
                [29750, 30249.99, 1500, 3030],
                [30250, 30749.99, 1525, 3080],
                [30750, 31249.99, 1550, 3130],
                [31250, 31749.99, 1575, 3180],
                [31750, 32249.99, 1600, 3230],
                [32250, 32749.99, 1625, 3280],
                [32750, 33249.99, 1650, 3330],
                [33250, 33749.99, 1675, 3380],
                [33750, 34249.99, 1700, 3430],
                [34250, 34749.99, 1725, 3480],
                [34750, null, 1750, 3530],       // Max MSC ₱35,000
            ];

            $rows = [];
            $now = now();

            foreach ($sssBrackets as [$min, $max, $empAmt, $erAmt]) {
                $rows[] = [
                    'type' => 'sss',
                    'effective_date' => '2025-01-01',
                    'min_salary' => $min,
                    'max_salary' => $max,
                    'employee_amount' => $empAmt,
                    'employer_amount' => $erAmt,
                    'employee_rate' => null,
                    'employer_rate' => null,
                    'min_contribution' => null,
                    'max_contribution' => null,
                    'notes' => $note,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            ContributionBracket::insert($rows);
        }

        // PhilHealth — 2023 UHC Law: 5% rate, ₱500 floor (₱250/side), ₱5,000 cap (₱2,500/side)
        if (! ContributionBracket::where('type', 'philhealth')->where('is_active', true)->exists()) {
            ContributionBracket::create([
                'type' => 'philhealth',
                'effective_date' => $today,
                'min_salary' => 0,
                'max_salary' => null,
                'employee_rate' => 0.025,
                'employer_rate' => 0.025,
                'employee_amount' => null,
                'employer_amount' => null,
                'min_contribution' => 250.00,
                'max_contribution' => 2500.00,
                'notes' => '2023 UHC Law — 5% premium, employee share 2.5%',
                'is_active' => true,
            ]);
        }

        // Pag-IBIG — employee 1% (≤₱1,500) or 2% (>₱1,500), max ₱200/month; employer 2%
        if (! ContributionBracket::where('type', 'pagibig')->where('is_active', true)->exists()) {
            ContributionBracket::insert([
                [
                    'type' => 'pagibig',
                    'effective_date' => $today,
                    'min_salary' => 0,
                    'max_salary' => 1500.00,
                    'employee_rate' => 0.01,
                    'employer_rate' => 0.02,
                    'employee_amount' => null,
                    'employer_amount' => null,
                    'min_contribution' => null,
                    'max_contribution' => 200.00,
                    'notes' => 'Salary ≤ ₱1,500 — employee 1%',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'type' => 'pagibig',
                    'effective_date' => $today,
                    'min_salary' => 1500.01,
                    'max_salary' => null,
                    'employee_rate' => 0.02,
                    'employer_rate' => 0.02,
                    'employee_amount' => null,
                    'employer_amount' => null,
                    'min_contribution' => null,
                    'max_contribution' => 200.00,
                    'notes' => 'Salary > ₱1,500 — employee 2%',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // Withholding Tax — BIR TRAIN Law (RA 10963), 2023 annual brackets
        // min_salary = min_income, employee_amount = base_tax, employee_rate = marginal rate
        if (! ContributionBracket::where('type', 'tax')->where('is_active', true)->exists()) {
            $taxBrackets = [
                ['min' => 0,         'base' => 0,          'rate' => 0,    'note' => '₱0 – ₱250,000: 0%'],
                ['min' => 250000,    'base' => 0,          'rate' => 0.15, 'note' => '₱250,001 – ₱400,000: 15% of excess'],
                ['min' => 400000,    'base' => 22500,      'rate' => 0.20, 'note' => '₱400,001 – ₱800,000: ₱22,500 + 20%'],
                ['min' => 800000,    'base' => 102500,     'rate' => 0.25, 'note' => '₱800,001 – ₱2,000,000: ₱102,500 + 25%'],
                ['min' => 2000000,   'base' => 402500,     'rate' => 0.30, 'note' => '₱2,000,001 – ₱8,000,000: ₱402,500 + 30%'],
                ['min' => 8000000,   'base' => 2202500,    'rate' => 0.35, 'note' => '₱8,000,001+: ₱2,202,500 + 35%'],
            ];

            foreach ($taxBrackets as $b) {
                ContributionBracket::create([
                    'type' => 'tax',
                    'effective_date' => $today,
                    'min_salary' => $b['min'],
                    'max_salary' => null,
                    'employee_rate' => $b['rate'],
                    'employer_rate' => null,
                    'employee_amount' => $b['base'],
                    'employer_amount' => null,
                    'min_contribution' => null,
                    'max_contribution' => null,
                    'notes' => $b['note'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
