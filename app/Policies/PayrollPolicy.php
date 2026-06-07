<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Payroll\Models\Holiday;
use App\Modules\Payroll\Models\Loan;
use App\Modules\Payroll\Models\PayrollItem;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Payroll\Models\PayrollSetting;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view_all')
            || $user->hasPermission('payroll.run')
            || $user->hasPermission('payroll.settings');
    }

    public function manageHolidays(User $user): bool
    {
        return $user->hasPermission('payroll.holidays');
    }

    public function manageLoans(User $user): bool
    {
        return $user->hasPermission('payroll.loans');
    }

    public function viewLoans(User $user): bool
    {
        return $user->hasPermission('payroll.loans') || $user->employee !== null;
    }

    public function view(User $user, PayrollPeriod|PayrollItem $resource): bool
    {
        $companyId = $user->employee?->company_id;

        if ($resource instanceof PayrollItem) {
            // Employees can only view own payslip
            if ($user->hasPermission('payroll.view_own') && (int) $resource->employee_id === (int) $user->employee?->id) {
                return true;
            }

            return $user->hasPermission('payroll.view_all') && (int) $resource->employee?->company_id === $companyId;
        }

        return $user->hasPermission('payroll.view_all') && (int) $resource->company_id === $companyId;
    }

    public function run(User $user, PayrollPeriod $period): bool
    {
        return $user->hasPermission('payroll.run')
            && (int) $period->company_id === (int) $user->employee?->company_id;
    }

    public function finalize(User $user, PayrollPeriod $period): bool
    {
        return $this->run($user, $period);
    }

    public function cancel(User $user, PayrollPeriod $period): bool
    {
        return $user->hasPermission('payroll.run')
            && (int) $period->company_id === (int) $user->employee?->company_id
            && $period->status === 'draft';
    }

    public function deletePeriod(User $user, PayrollPeriod $period): bool
    {
        return $user->hasPermission('payroll.run')
            && (int) $period->company_id === (int) $user->employee?->company_id
            && in_array($period->status, ['draft', 'cancelled']);
    }

    public function viewAnySettings(User $user): bool
    {
        return $user->hasPermission('payroll.settings');
    }

    public function update(User $user, PayrollSetting|Holiday|Loan $resource): bool
    {
        if ($resource instanceof Loan) {
            return $user->hasPermission('payroll.loans')
                && (int) $resource->company_id === (int) $user->employee?->company_id;
        }

        if ($resource instanceof Holiday) {
            return $user->hasPermission('payroll.holidays')
                && ($resource->company_id === null || (int) $resource->company_id === (int) $user->employee?->company_id);
        }

        return $user->hasPermission('payroll.settings');
    }

    public function delete(User $user, Holiday|Loan $resource): bool
    {
        return $this->update($user, $resource);
    }
}
