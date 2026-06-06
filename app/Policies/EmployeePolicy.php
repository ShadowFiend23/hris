<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Core\Models\Employee;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any employees.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('hris.employees.view');
    }

    /**
     * Determine whether the user can view the employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        if ((int) $user->company_id !== (int) $employee->company_id) {
            return false;
        }

        // Admins can view anyone
        if ($user->hasRole('admin')) {
            return true;
        }

        // Employees can view their own record
        if ($user->id === $employee->user_id) {
            return true;
        }

        // Managers can only view employees within their reporting subtree
        if ($user->hasRole('manager')) {
            $managerEmployee = \App\Modules\Core\Models\Employee::where('user_id', $user->id)->first();

            return $managerEmployee && in_array($employee->id, $managerEmployee->getAllSubordinateIds(), true);
        }

        return $user->hasPermission('hris.employees.view');
    }

    /**
     * Determine whether the user can create employees.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('hris.employees.create');
    }

    /**
     * Determine whether the user can update the employee.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermission('hris.employees.edit')
            && (int) $user->company_id === (int) $employee->company_id;
    }

    /**
     * Determine whether the user can delete the employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        if ($employee->user_id === $user->id) {
            return false;
        }

        return $user->hasPermission('hris.employees.delete')
            && (int) $user->company_id === (int) $employee->company_id;
    }

    /**
     * Determine whether the user can restore the employee.
     */
    public function restore(User $user, Employee $employee): bool
    {
        return $user->hasPermission('hris.employees.edit')
            && (int) $user->company_id === (int) $employee->company_id;
    }

    /**
     * Determine whether the user can permanently delete the employee.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        // For now, permanent deletion is not allowed
        return false;
    }
}
