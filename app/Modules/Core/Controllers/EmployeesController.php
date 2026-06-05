<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\User;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Position;
use App\Modules\Core\Services\EmployeeService;
use App\Modules\Payroll\Models\PayrollItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmployeesController extends Controller
{
    protected EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Display a listing of employees.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = Auth::user();
        $companyId = $this->getCompanyId();

        // Employees can only see their own record — redirect to their detail page
        if ($user->hasRole('employee') && ! $user->hasRole('admin') && ! $user->hasRole('manager')) {
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee) {
                return redirect()->route('employees.show', $employee);
            }
            abort(403, 'No employee record linked to your account.');
        }

        // Build filters from request
        $filters = [
            'search' => $request->input('search'),
            'department_id' => $request->input('department_id'),
            'position_id' => $request->input('position_id'),
            'employment_status' => $request->input('employment_status'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_direction' => $request->input('sort_direction', 'desc'),
        ];

        $perPage = max(5, min(100, (int) $request->input('per_page', 5)));

        // Managers see only their department's employees
        if ($user->hasRole('manager') && ! $user->hasRole('admin')) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            if ($managerEmployee) {
                $filters['department_id'] = $filters['department_id'] ?: $managerEmployee->department_id;
            }
        }

        // Get paginated employees
        $employees = $this->employeeService->getEmployeesByCompany($companyId, $filters, $perPage);

        // Get departments and positions for filters
        $departments = Department::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $positions = Position::whereHas('department', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->where('is_active', true)
            ->select('id', 'position_name', 'department_id')
            ->orderBy('position_name')
            ->get();

        return Inertia::render('Employees/Employees', [
            'employees' => $employees,
            'departments' => $departments,
            'positions' => $positions,
            'filters' => $filters,
            'employmentStatuses' => $this->getEmploymentStatuses(),
        ]);
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): Response
    {
        $companyId = $this->getCompanyId();

        $departments = Department::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $positions = Position::where('is_active', true)
            ->select('id', 'position_name', 'department_id')
            ->orderBy('position_name')
            ->get();

        return Inertia::render('Employees/EmployeeForm', [
            'mode' => 'create',
            'departments' => $departments,
            'positions' => $positions,
            'employmentStatuses' => $this->getEmploymentStatuses(),
            'employmentTypes' => $this->getEmploymentTypes(),
            'genderOptions' => $this->getGenderOptions(),
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('employees/photos', 'public');
        }

        $employee = $this->employeeService->createEmployee($data);

        // Log audit trail
        Log::info('Employee created', [
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_id,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', "Employee {$employee->full_name} has been created successfully.");
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): Response
    {
        // Authorize: ensure employee belongs to user's company
        $this->authorizeEmployee($employee);

        // Load relationships
        $employee->load(['department', 'position', 'user', 'company']);

        $payrollItems = PayrollItem::where('employee_id', $employee->id)
            ->with(['period:id,start_date,end_date,pay_date,status'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get(['id', 'payroll_period_id', 'gross_pay', 'total_deductions', 'net_pay', 'status', 'days_worked', 'created_at']);

        return Inertia::render('Employees/EmployeeDetail', [
            'employee' => $employee,
            'payroll_items' => $payrollItems,
        ]);
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee): Response
    {
        // Authorize: ensure employee belongs to user's company
        $this->authorizeEmployee($employee);

        $companyId = $this->getCompanyId();

        $departments = Department::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $positions = Position::where('is_active', true)
            ->select('id', 'position_name', 'department_id')
            ->orderBy('position_name')
            ->get();

        return Inertia::render('Employees/EmployeeForm', [
            'mode' => 'edit',
            'employee' => $employee->load(['department', 'position']),
            'departments' => $departments,
            'positions' => $positions,
            'employmentStatuses' => $this->getEmploymentStatuses(),
            'employmentTypes' => $this->getEmploymentTypes(),
            'genderOptions' => $this->getGenderOptions(),
        ]);
    }

    /**
     * Update the specified employee.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        // Authorize: ensure employee belongs to user's company
        $this->authorizeEmployee($employee);

        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            if ($employee->profile_photo_path) {
                Storage::disk('public')->delete($employee->profile_photo_path);
            }
            $data['profile_photo_path'] = $request->file('profile_photo')->store('employees/photos', 'public');
        }

        $this->employeeService->updateEmployee($employee, $data);

        // Log audit trail
        Log::info('Employee updated', [
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_id,
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', "Employee {$employee->full_name} has been updated successfully.");
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        // Authorize: ensure employee belongs to user's company
        $this->authorizeEmployee($employee);

        $employeeName = $employee->full_name;

        $this->employeeService->deleteEmployee($employee);

        // Log audit trail
        Log::info('Employee deleted', [
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_id,
            'deleted_by' => Auth::id(),
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', "Employee {$employeeName} has been deleted successfully.");
    }

    /**
     * Restore a soft-deleted employee.
     */
    public function restore(int $id): RedirectResponse
    {
        $companyId = $this->getCompanyId();

        // Find the soft-deleted employee
        $employee = Employee::withTrashed()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (! $employee) {
            abort(404, 'Employee not found.');
        }

        if (! $employee->trashed()) {
            return redirect()
                ->route('employees.show', $employee)
                ->with('info', "Employee {$employee->full_name} is not deleted.");
        }

        $this->employeeService->restoreEmployee($employee);

        // Log audit trail
        Log::info('Employee restored', [
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_id,
            'restored_by' => Auth::id(),
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', "Employee {$employee->full_name} has been restored successfully.");
    }

    /**
     * Authorize that the employee belongs to the current user's company.
     */
    private function authorizeEmployee(Employee $employee): void
    {
        if ((int) $employee->company_id !== $this->getCompanyId()) {
            abort(403, 'Unauthorized access to this employee.');
        }
    }

    /**
     * Get the authenticated user's company ID.
     */
    private function getCompanyId(): int
    {
        $user = Auth::user();

        if (! $user || ! $user->company_id) {
            abort(403, 'You must be associated with a company to access this resource.');
        }

        return $user->company_id;
    }

    /**
     * Get employment status options.
     */
    private function getEmploymentStatuses(): array
    {
        return [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
            ['value' => 'resigned', 'label' => 'Resigned'],
            ['value' => 'terminated', 'label' => 'Terminated'],
            ['value' => 'retired', 'label' => 'Retired'],
        ];
    }

    /**
     * Get employment type options.
     */
    private function getEmploymentTypes(): array
    {
        return [
            ['value' => 'full_time', 'label' => 'Full Time'],
            ['value' => 'part_time', 'label' => 'Part Time'],
            ['value' => 'contract', 'label' => 'Contract'],
            ['value' => 'probationary', 'label' => 'Probationary'],
        ];
    }

    /**
     * Get gender options.
     */
    private function getGenderOptions(): array
    {
        return [
            ['value' => 'male', 'label' => 'Male'],
            ['value' => 'female', 'label' => 'Female'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }
}
