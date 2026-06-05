<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\AllowanceType;
use App\Modules\Payroll\Models\EmployeeAllowance;
use Illuminate\Database\Seeder;

class EmployeeAllowanceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'test-company')->firstOrFail();

        // Load the allowance types we want to assign
        $riceType = AllowanceType::where('company_id', $company->id)->where('code', 'rice')->first();
        $transportType = AllowanceType::where('company_id', $company->id)->where('code', 'transport')->first();
        $mealType = AllowanceType::where('company_id', $company->id)->where('code', 'meal')->first();

        if (! $riceType || ! $transportType || ! $mealType) {
            $this->command->warn('AllowanceTypeSeeder must run before EmployeeAllowanceSeeder.');

            return;
        }

        $typesToSeed = [$riceType, $transportType, $mealType];

        // Only regular (full_time) employees receive allowances
        $regularEmployees = Employee::where('company_id', $company->id)
            ->where('employment_type', 'full_time')
            ->where('is_active', true)
            ->get();

        foreach ($regularEmployees as $employee) {
            foreach ($typesToSeed as $allowanceType) {
                EmployeeAllowance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'allowance_type_id' => $allowanceType->id,
                    ],
                    [
                        'company_id' => $employee->company_id,
                        'type' => $allowanceType->code,
                        'name' => $allowanceType->name,
                        'amount' => $allowanceType->default_amount,
                        'is_taxable' => $allowanceType->is_taxable,
                        'frequency' => 'monthly',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
