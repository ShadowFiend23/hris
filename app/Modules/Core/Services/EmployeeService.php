<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    /**
     * Get all employees.
     */
    public function getAllEmployee(): Collection
    {
        return Employee::all();
    }

    /**
     * Get employees with pagination.
     */
    public function getEmployeeWithPagination(int $page = 1, int $limit = 10): Collection
    {
        return Employee::skip(($page - 1) * $limit)
            ->take($limit)
            ->get();
    }

    /**
     * Get employee by ID with relationships.
     */
    public function getEmployeeById(int $id): ?Employee
    {
        return Employee::with(['position', 'department', 'company'])->find($id);
    }

    /**
     * Get employees by company with filters and pagination.
     */
    public function getEmployeesByCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::with(['department', 'position', 'user'])
            ->where('company_id', $companyId);

        // Filter by department
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // Filter by position
        if (! empty($filters['position_id'])) {
            $query->where('position_id', $filters['position_id']);
        }

        // Filter by employment status
        if (! empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Restrict to an explicit set of employee IDs (used to scope manager visibility to their subtree)
        if (! empty($filters['employee_ids'])) {
            $query->whereIn('id', $filters['employee_ids']);
        }

        // Search by name, employee_id, or email
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $allowedSortFields = ['first_name', 'last_name', 'employee_id', 'date_hired', 'created_at'];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new employee.
     */
    public function createEmployee(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $companyId = Auth::user()->company_id;

            // Generate unique employee_id if not provided
            if (empty($data['employee_id'])) {
                $data['employee_id'] = $this->generateEmployeeId($companyId);
            }

            // Set company_id from authenticated user
            $data['company_id'] = $companyId;

            // Set default values
            $data['is_active'] = $data['is_active'] ?? true;
            $data['employment_status'] = $data['employment_status'] ?? 'active';

            // Create employee
            $employee = Employee::create($data);

            // Load relationships
            return $employee->load(['department', 'position', 'company']);
        });
    }

    /**
     * Update an existing employee.
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            // Handle nullable fields
            $nullableFields = [
                'middle_name', 'date_of_birth', 'gender', 'phone',
                'address', 'city', 'province', 'postal_code',
                'date_resigned', 'salary', 'bank_account',
                'tin', 'sss_number', 'philhealth_number', 'pagibig_number',
            ];

            foreach ($nullableFields as $field) {
                if (array_key_exists($field, $data) && $data[$field] === '') {
                    $data[$field] = null;
                }
            }

            // Update employee
            $employee->update($data);

            // Load relationships
            return $employee->load(['department', 'position', 'company']);
        });
    }

    /**
     * Soft delete an employee.
     */
    public function deleteEmployee(Employee $employee): bool
    {
        return DB::transaction(function () use ($employee) {
            // Set is_active to false
            $employee->is_active = false;

            // Nullify user_id if linked to a user account
            if ($employee->user_id) {
                $employee->user_id = null;
            }

            $employee->save();

            // Perform soft delete
            return $employee->delete();
        });
    }

    /**
     * Restore a soft-deleted employee.
     */
    public function restoreEmployee(int $id): ?Employee
    {
        $employee = Employee::withTrashed()->find($id);

        if (! $employee) {
            return null;
        }

        $employee->restore();
        $employee->is_active = true;
        $employee->save();

        return $employee->load(['department', 'position', 'company']);
    }

    /**
     * Generate a unique employee ID for a company.
     */
    private function generateEmployeeId(int $companyId): string
    {
        $prefix = 'EMP';
        $lastEmployee = Employee::where('company_id', $companyId)
            ->where('employee_id', 'like', "{$prefix}-%")
            ->orderByRaw('CAST(SUBSTRING(employee_id, 5) AS UNSIGNED) DESC')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_id, 4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%04d', $prefix, $newNumber);
    }
}
