<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use App\Modules\Timekeeping\Models\WorkPolicy;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OvertimeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'test-company')->firstOrFail();
        $policy = WorkPolicy::where('company_id', $company->id)->where('is_active', true)->first();
        $approver = User::where('company_id', $company->id)->first();

        // One approved record of each overtime type, aligned with seeded attendance dates.
        $plan = [
            ['employee_id' => 'EMP-009', 'type' => 'weekday', 'date' => Carbon::now()->subDays(7)->next(Carbon::TUESDAY), 'hours' => 2.0],
            ['employee_id' => 'EMP-010', 'type' => 'weekend', 'date' => Carbon::now()->subDays(7)->next(Carbon::SATURDAY), 'hours' => 3.0],
            ['employee_id' => 'EMP-011', 'type' => 'holiday', 'date' => Carbon::create(Carbon::now()->year, 5, 1), 'hours' => 4.0],
        ];

        foreach ($plan as $row) {
            $employee = Employee::where('company_id', $company->id)
                ->where('employee_id', $row['employee_id'])
                ->first();

            if (! $employee) {
                continue;
            }

            $multiplier = $policy ? $policy->getOvertimeRate($row['type']) : match ($row['type']) {
                'weekend' => 1.50,
                'holiday' => 2.00,
                default => 1.25,
            };

            OvertimeRecord::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $row['date']->toDateString(),
                ],
                [
                    'company_id' => $company->id,
                    'hours' => $row['hours'],
                    'overtime_type' => $row['type'],
                    'reason' => 'Seeded '.$row['type'].' overtime',
                    'pay_rate_multiplier' => $multiplier,
                    'status' => 'approved',
                    'approved_by' => $approver?->id,
                    'approved_at' => now(),
                ]
            );
        }
    }
}
