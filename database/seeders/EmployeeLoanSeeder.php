<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\Loan;
use App\Modules\Payroll\Models\LoanType;
use Illuminate\Database\Seeder;

class EmployeeLoanSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'test-company')->firstOrFail();

        // Loans only deduct when this flag is true on the company
        $company->update(['loans_enabled' => true]);

        $sssLoanType = LoanType::where('code', 'sss_loan')->first();
        $pagibigLoanType = LoanType::where('code', 'pagibig_loan')->first();
        $companyLoanType = LoanType::where('code', 'company_loan')->first();

        if (! $sssLoanType || ! $pagibigLoanType || ! $companyLoanType) {
            $this->command->warn('LoanTypeSeeder must run before EmployeeLoanSeeder.');

            return;
        }

        $employees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->get()
            ->keyBy('employee_id');

        $loanDefinitions = [
            // Carlo Mendoza (₱38k) — SSS salary loan, 12 months, partially paid
            'EMP-009' => [
                'type' => 'sss_loan',
                'loan_type_id' => $sssLoanType->id,
                'principal' => 24000.00,
                'balance' => 18000.00,
                'monthly_amortization' => 2000.00,
                'start_date' => now()->subMonths(3)->startOfMonth(),
                'end_date' => now()->addMonths(9)->endOfMonth(),
                'notes' => 'SSS salary loan',
            ],
            // Eduardo Lopez (₱36k) — Pag-IBIG multi-purpose loan
            'EMP-013' => [
                'type' => 'pagibig_loan',
                'loan_type_id' => $pagibigLoanType->id,
                'principal' => 50000.00,
                'balance' => 42000.00,
                'monthly_amortization' => 2000.00,
                'start_date' => now()->subMonths(4)->startOfMonth(),
                'end_date' => now()->addMonths(21)->endOfMonth(),
                'notes' => 'Pag-IBIG multi-purpose loan',
            ],
            // Dante Rivera (₱30k) — company emergency loan
            'EMP-017' => [
                'type' => 'company_loan',
                'loan_type_id' => $companyLoanType->id,
                'principal' => 30000.00,
                'balance' => 25000.00,
                'monthly_amortization' => 2500.00,
                'start_date' => now()->subMonths(2)->startOfMonth(),
                'end_date' => now()->addMonths(10)->endOfMonth(),
                'notes' => 'Emergency company loan',
            ],
            // Jose Cruz (₱65k) — SSS loan nearly paid off
            'EMP-005' => [
                'type' => 'sss_loan',
                'loan_type_id' => $sssLoanType->id,
                'principal' => 24000.00,
                'balance' => 6000.00,
                'monthly_amortization' => 2000.00,
                'start_date' => now()->subMonths(9)->startOfMonth(),
                'end_date' => now()->addMonths(3)->endOfMonth(),
                'notes' => 'SSS salary loan',
            ],
            // Miriam Castillo (₱29k) — Pag-IBIG calamity loan
            'EMP-018' => [
                'type' => 'pagibig_loan',
                'loan_type_id' => $pagibigLoanType->id,
                'principal' => 20000.00,
                'balance' => 16000.00,
                'monthly_amortization' => 1000.00,
                'start_date' => now()->subMonths(4)->startOfMonth(),
                'end_date' => now()->addMonths(16)->endOfMonth(),
                'notes' => 'Pag-IBIG calamity loan',
            ],
        ];

        foreach ($loanDefinitions as $employeeId => $loanData) {
            $employee = $employees->get($employeeId);

            if (! $employee) {
                $this->command->warn("Employee {$employeeId} not found — skipping loan.");

                continue;
            }

            Loan::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'type' => $loanData['type'],
                    'status' => 'active',
                ],
                array_merge($loanData, [
                    'employee_id' => $employee->id,
                    'company_id' => $company->id,
                    'status' => 'active',
                ])
            );
        }
    }
}
