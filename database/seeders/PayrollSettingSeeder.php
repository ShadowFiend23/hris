<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Company;
use App\Modules\Payroll\Models\PayrollSetting;
use Illuminate\Database\Seeder;

class PayrollSettingSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            PayrollSetting::firstOrCreate(
                ['company_id' => $company->id],
                [
                    'period_type' => 'semi_monthly',
                    'pay_day_1' => 15,
                    'pay_day_2' => 30,
                    'work_days_per_month' => 26,
                    'is_active' => true,
                ]
            );
        }
    }
}
