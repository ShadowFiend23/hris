<?php

namespace Database\Seeders;

use App\Modules\Payroll\Models\LoanType;
use Illuminate\Database\Seeder;

class LoanTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['code' => 'sss_loan', 'name' => 'SSS Loan', 'description' => 'Social Security System loan'],
            ['code' => 'pagibig_loan', 'name' => 'Pag-IBIG Loan', 'description' => 'Pag-IBIG Fund (HDMF) loan'],
            ['code' => 'company_loan', 'name' => 'Company Loan', 'description' => 'Internal company loan'],
        ];

        foreach ($defaults as $type) {
            LoanType::firstOrCreate(
                ['company_id' => null, 'code' => $type['code']],
                array_merge($type, ['company_id' => null, 'is_active' => true])
            );
        }
    }
}
