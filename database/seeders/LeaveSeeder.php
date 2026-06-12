<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\LeaveBalance;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeaveSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'test-company')->firstOrFail();
        $approver = User::where('company_id', $company->id)->first();

        $vacation = LeaveType::where('company_id', $company->id)->where('code', 'VL')->first();

        $lwop = LeaveType::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'LWOP'],
            [
                'name' => 'Leave Without Pay',
                'days_per_year' => 0,
                'is_paid' => false,
                'requires_approval' => true,
                'is_active' => true,
            ]
        );

        // Approved PAID leave (2 work days) for one employee.
        $paidEmployee = Employee::where('company_id', $company->id)->where('employee_id', 'EMP-012')->first();
        if ($paidEmployee && $vacation) {
            $start = Carbon::now()->subDays(10)->next(Carbon::MONDAY);
            $end = $start->copy()->addDay(); // Mon–Tue
            $this->createApprovedLeave($paidEmployee, $vacation, $start, $end, $approver, deductBalance: true);
        }

        // Approved UNPAID leave (1 work day) for another employee.
        $unpaidEmployee = Employee::where('company_id', $company->id)->where('employee_id', 'EMP-016')->first();
        if ($unpaidEmployee) {
            $start = Carbon::now()->subDays(9)->next(Carbon::WEDNESDAY);
            $this->createApprovedLeave($unpaidEmployee, $lwop, $start, $start->copy(), $approver, deductBalance: false);
        }
    }

    private function createApprovedLeave(
        Employee $employee,
        LeaveType $type,
        Carbon $start,
        Carbon $end,
        ?User $approver,
        bool $deductBalance
    ): void {
        $totalDays = $this->workDaysBetween($start, $end);

        LeaveRequest::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'start_date' => $start->toDateString(),
            ],
            [
                'end_date' => $end->toDateString(),
                'total_days' => $totalDays,
                'reason' => 'Seeded '.($type->is_paid ? 'paid' : 'unpaid').' leave',
                'status' => 'approved',
                'requested_at' => now(),
                'approved_by' => $approver?->id,
                'approved_at' => now(),
            ]
        );

        if ($deductBalance) {
            $balance = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $type->id)
                ->where('year', $start->year)
                ->first();

            if ($balance) {
                $balance->update([
                    'used_days' => (float) $balance->used_days + $totalDays,
                    'remaining_days' => max(0, (float) $balance->remaining_days - $totalDays),
                ]);
            }
        }
    }

    private function workDaysBetween(Carbon $start, Carbon $end): float
    {
        $days = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ((int) $current->format('N') <= 5) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }
}
