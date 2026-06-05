<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Company;
use App\Modules\Payroll\Models\AllowanceType;
use Illuminate\Database\Seeder;

class AllowanceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'test-company')->firstOrFail();

        $types = [
            [
                'code' => 'rice',
                'name' => 'Rice Subsidy',
                'default_amount' => 1500.00,
                'is_taxable' => false,
            ],
            [
                'code' => 'transport',
                'name' => 'Transportation Allowance',
                'default_amount' => 2000.00,
                'is_taxable' => false,
            ],
            [
                'code' => 'meal',
                'name' => 'Meal Allowance',
                'default_amount' => 2000.00,
                'is_taxable' => false,
            ],
            [
                'code' => 'clothing',
                'name' => 'Clothing Allowance',
                'default_amount' => 1000.00,
                'is_taxable' => false,
            ],
            [
                'code' => 'gas',
                'name' => 'Gas Allowance',
                'default_amount' => 1500.00,
                'is_taxable' => false,
            ],
        ];

        foreach ($types as $type) {
            AllowanceType::firstOrCreate(
                ['company_id' => $company->id, 'code' => $type['code']],
                array_merge($type, ['company_id' => $company->id, 'is_active' => true])
            );
        }
    }
}
