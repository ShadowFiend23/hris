<?php

namespace Database\Seeders;

use App\Modules\Payroll\Models\ContributionBracket;
use Illuminate\Database\Seeder;

class ContributionBracketSeeder extends Seeder
{
    public function run(): void
    {
        // Deactivate all existing brackets first
        ContributionBracket::where('is_active', true)->update(['is_active' => false]);

        $this->seedSSS();
        $this->seedPhilHealth();
        $this->seedPagibig();
    }

    /**
     * SSS 2025 Contribution Table
     * Source: SSS Circular No. 2024-005
     * Employee: 4.5%, Employer: 9.5%
     * Brackets stored as fixed employee/employer amounts per range.
     */
    private function seedSSS(): void
    {
        // Each row: [min, max|null, employee_amount, employer_amount]
        $brackets = [
            [1000, 3249.99, 135.00, 285.00],
            [3250, 3749.99, 157.50, 332.50],
            [3750, 4249.99, 180.00, 380.00],
            [4250, 4749.99, 202.50, 427.50],
            [4750, 5249.99, 225.00, 475.00],
            [5250, 5749.99, 247.50, 522.50],
            [5750, 6249.99, 270.00, 570.00],
            [6250, 6749.99, 292.50, 617.50],
            [6750, 7249.99, 315.00, 665.00],
            [7250, 7749.99, 337.50, 712.50],
            [7750, 8249.99, 360.00, 760.00],
            [8250, 8749.99, 382.50, 807.50],
            [8750, 9249.99, 405.00, 855.00],
            [9250, 9749.99, 427.50, 902.50],
            [9750, 10249.99, 450.00, 950.00],
            [10250, 10749.99, 472.50, 997.50],
            [10750, 11249.99, 495.00, 1045.00],
            [11250, 11749.99, 517.50, 1092.50],
            [11750, 12249.99, 540.00, 1140.00],
            [12250, 12749.99, 562.50, 1187.50],
            [12750, 13249.99, 585.00, 1235.00],
            [13250, 13749.99, 607.50, 1282.50],
            [13750, 14249.99, 630.00, 1330.00],
            [14250, 14749.99, 652.50, 1377.50],
            [14750, 15249.99, 675.00, 1425.00],
            [15250, 15749.99, 697.50, 1472.50],
            [15750, 16249.99, 720.00, 1520.00],
            [16250, 16749.99, 742.50, 1567.50],
            [16750, 17249.99, 765.00, 1615.00],
            [17250, 17749.99, 787.50, 1662.50],
            [17750, 18249.99, 810.00, 1710.00],
            [18250, 18749.99, 832.50, 1757.50],
            [18750, 19249.99, 855.00, 1805.00],
            [19250, 19749.99, 877.50, 1852.50],
            [19750, 20249.99, 900.00, 1900.00],
            [20250, 20749.99, 922.50, 1947.50],
            [20750, 21249.99, 945.00, 1995.00],
            [21250, 21749.99, 967.50, 2042.50],
            [21750, 22249.99, 990.00, 2090.00],
            [22250, 22749.99, 1012.50, 2137.50],
            [22750, 23249.99, 1035.00, 2185.00],
            [23250, 23749.99, 1057.50, 2232.50],
            [23750, 24249.99, 1080.00, 2280.00],
            [24250, 24749.99, 1102.50, 2327.50],
            [24750, 25249.99, 1125.00, 2375.00],
            [25250, 25749.99, 1147.50, 2422.50],
            [25750, 26249.99, 1170.00, 2470.00],
            [26250, 26749.99, 1192.50, 2517.50],
            [26750, 27249.99, 1215.00, 2565.00],
            [27250, 27749.99, 1237.50, 2612.50],
            [27750, 28249.99, 1260.00, 2660.00],
            [28250, 28749.99, 1282.50, 2707.50],
            [28750, 29249.99, 1305.00, 2755.00],
            [29250, 29749.99, 1327.50, 2802.50],
            [29750, 30249.99, 1350.00, 2850.00],
            [30250, null, 1350.00, 2850.00], // Capped at ₱30,000 MSC
        ];

        foreach ($brackets as [$min, $max, $empAmt, $erAmt]) {
            ContributionBracket::create([
                'type' => 'sss',
                'effective_date' => '2025-01-01',
                'min_salary' => $min,
                'max_salary' => $max,
                'employee_rate' => null,
                'employer_rate' => null,
                'employee_amount' => $empAmt,
                'employer_amount' => $erAmt,
                'is_active' => true,
            ]);
        }
    }

    /**
     * PhilHealth 2025
     * Premium rate: 5% of monthly basic salary
     * Floor: ₱500/month total | Cap: ₱5,000/month total
     * Single bracket — logic handled in PhilHealthContributionService
     */
    private function seedPhilHealth(): void
    {
        ContributionBracket::create([
            'type' => 'philhealth',
            'effective_date' => '2025-01-01',
            'min_salary' => 0,
            'max_salary' => null,
            'employee_rate' => 0.025, // 2.5% employee share
            'employer_rate' => 0.025, // 2.5% employer share
            'employee_amount' => null,
            'employer_amount' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Pag-IBIG 2025
     * ≤ ₱1,500: 1% employee, 2% employer
     * > ₱1,500: 2% employee (max ₱200), 2% employer
     */
    private function seedPagibig(): void
    {
        ContributionBracket::create([
            'type' => 'pagibig',
            'effective_date' => '2025-01-01',
            'min_salary' => 0,
            'max_salary' => 1500,
            'employee_rate' => 0.01,
            'employer_rate' => 0.02,
            'employee_amount' => null,
            'employer_amount' => null,
            'is_active' => true,
        ]);

        ContributionBracket::create([
            'type' => 'pagibig',
            'effective_date' => '2025-01-01',
            'min_salary' => 1500.01,
            'max_salary' => null,
            'employee_rate' => 0.02,
            'employer_rate' => 0.02,
            'employee_amount' => null,
            'employer_amount' => null,
            'is_active' => true,
        ]);
    }
}
