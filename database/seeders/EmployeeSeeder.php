<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Position;
use App\Modules\Core\Models\Role;
use App\Modules\Timekeeping\Models\LeaveBalance;
use App\Modules\Timekeeping\Models\LeaveType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'test-company')->firstOrFail();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $managerRole = Role::where('slug', 'manager')->firstOrFail();
        $employeeRole = Role::where('slug', 'employee')->firstOrFail();

        // --- Departments ---
        $itDept = Department::create([
            'company_id' => $company->id,
            'name' => 'IT Department',
            'slug' => 'it-department',
            'description' => 'Information Technology Department',
            'is_active' => true,
        ]);

        $opsDept = Department::create([
            'company_id' => $company->id,
            'name' => 'Operations Department',
            'slug' => 'operations-department',
            'description' => 'Operations Department',
            'is_active' => true,
        ]);

        // --- Positions (IT) ---
        $posItDirector = Position::create(['department_id' => $itDept->id, 'position_name' => 'IT Director', 'is_active' => true]);
        $posItLead = Position::create(['department_id' => $itDept->id, 'position_name' => 'IT Team Lead', 'is_active' => true]);
        $posDeveloper = Position::create(['department_id' => $itDept->id, 'position_name' => 'Developer', 'is_active' => true]);

        // --- Positions (Operations) ---
        $posOpsDirector = Position::create(['department_id' => $opsDept->id, 'position_name' => 'Operations Director', 'is_active' => true]);
        $posOpsSupervisor = Position::create(['department_id' => $opsDept->id, 'position_name' => 'Operations Supervisor', 'is_active' => true]);
        $posOpsAssociate = Position::create(['department_id' => $opsDept->id, 'position_name' => 'Operations Associate', 'is_active' => true]);

        // --- Tier 1: Directors (Manager role) ---
        $emp003 = $this->createEmployee($company, $itDept, $posItDirector, $managerRole, [
            'employee_id' => 'EMP-003', 'first_name' => 'Ricardo', 'last_name' => 'Santos',
            'alpeta_employee_id' => '2063', 'salary' => 90000,
            'gender' => 'male', 'date_of_birth' => '1978-03-12',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(36),
        ]);

        $emp004 = $this->createEmployee($company, $opsDept, $posOpsDirector, $managerRole, [
            'employee_id' => 'EMP-004', 'first_name' => 'Maria', 'last_name' => 'Reyes',
            'alpeta_employee_id' => '2064', 'salary' => 85000,
            'gender' => 'female', 'date_of_birth' => '1980-07-25',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(34),
        ]);

        // --- Tier 2: Team Leads (Manager role) ---
        $emp005 = $this->createEmployee($company, $itDept, $posItLead, $managerRole, [
            'employee_id' => 'EMP-005', 'first_name' => 'Jose', 'last_name' => 'Cruz',
            'alpeta_employee_id' => '2069', 'salary' => 65000,
            'gender' => 'male', 'date_of_birth' => '1985-11-08',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(28),
        ]);

        $emp006 = $this->createEmployee($company, $itDept, $posItLead, $managerRole, [
            'employee_id' => 'EMP-006', 'first_name' => 'Ana', 'last_name' => 'Dela Cruz',
            'alpeta_employee_id' => '2072', 'salary' => 62000,
            'gender' => 'female', 'date_of_birth' => '1987-02-14',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(26),
        ]);

        $emp007 = $this->createEmployee($company, $opsDept, $posOpsSupervisor, $managerRole, [
            'employee_id' => 'EMP-007', 'first_name' => 'Pedro', 'last_name' => 'Ramos',
            'alpeta_employee_id' => '2074', 'salary' => 58000,
            'gender' => 'male', 'date_of_birth' => '1983-09-30',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(24),
        ]);

        $emp008 = $this->createEmployee($company, $opsDept, $posOpsSupervisor, $managerRole, [
            'employee_id' => 'EMP-008', 'first_name' => 'Lorna', 'last_name' => 'Villanueva',
            'alpeta_employee_id' => '2077', 'salary' => 55000,
            'gender' => 'female', 'date_of_birth' => '1986-06-18',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(22),
        ]);

        // --- Tier 3: Regular Employees (Employee role) ---
        // Under Jose Cruz (EMP-005)
        $this->createEmployee($company, $itDept, $posDeveloper, $employeeRole, [
            'employee_id' => 'EMP-009', 'first_name' => 'Carlo', 'last_name' => 'Mendoza',
            'alpeta_employee_id' => '2078', 'salary' => 38000,
            'gender' => 'male', 'date_of_birth' => '1993-04-05',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(16),
        ]);

        $this->createEmployee($company, $itDept, $posDeveloper, $employeeRole, [
            'employee_id' => 'EMP-010', 'first_name' => 'Patricia', 'last_name' => 'Garcia',
            'alpeta_employee_id' => '2080', 'salary' => 36000,
            'gender' => 'female', 'date_of_birth' => '1994-08-22',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(14),
        ]);

        $this->createEmployee($company, $itDept, $posDeveloper, $employeeRole, [
            'employee_id' => 'EMP-011', 'first_name' => 'Michael', 'last_name' => 'Torres',
            'alpeta_employee_id' => '2083', 'salary' => 37000,
            'gender' => 'male', 'date_of_birth' => '1992-12-01',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(15),
        ]);

        $this->createEmployee($company, $itDept, $posDeveloper, $employeeRole, [
            'employee_id' => 'EMP-012', 'first_name' => 'Rosario', 'last_name' => 'Aquino',
            'alpeta_employee_id' => '2084', 'salary' => 35000,
            'gender' => 'female', 'date_of_birth' => '1995-03-17',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(10),
        ]);

        // Under Ana Dela Cruz (EMP-006)
        $this->createEmployee($company, $itDept, $posDeveloper, $employeeRole, [
            'employee_id' => 'EMP-013', 'first_name' => 'Eduardo', 'last_name' => 'Lopez',
            'alpeta_employee_id' => '2085', 'salary' => 36000,
            'gender' => 'male', 'date_of_birth' => '1991-10-11',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(9),
        ]);

        $this->createEmployee($company, $itDept, $posDeveloper, $employeeRole, [
            'employee_id' => 'EMP-014', 'first_name' => 'Jennifer', 'last_name' => 'Martinez',
            'alpeta_employee_id' => '2086', 'salary' => 35000,
            'gender' => 'female', 'date_of_birth' => '1996-01-29',
            'employment_type' => 'probationary', 'date_hired' => now()->subMonths(4),
        ]);

        $this->createEmployee($company, $itDept, $posDeveloper, $employeeRole, [
            'employee_id' => 'EMP-015', 'first_name' => 'Roberto', 'last_name' => 'Navarro',
            'alpeta_employee_id' => '2087', 'salary' => 34000,
            'gender' => 'male', 'date_of_birth' => '1993-07-07',
            'employment_type' => 'probationary', 'date_hired' => now()->subMonths(3),
        ]);

        $this->createEmployee($company, $itDept, $posDeveloper, $employeeRole, [
            'employee_id' => 'EMP-016', 'first_name' => 'Carmela', 'last_name' => 'Fernandez',
            'alpeta_employee_id' => '2091', 'salary' => 33000,
            'gender' => 'female', 'date_of_birth' => '1997-05-23',
            'employment_type' => 'probationary', 'date_hired' => now()->subMonths(2),
        ]);

        // Under Pedro Ramos (EMP-007)
        $this->createEmployee($company, $opsDept, $posOpsAssociate, $employeeRole, [
            'employee_id' => 'EMP-017', 'first_name' => 'Dante', 'last_name' => 'Rivera',
            'alpeta_employee_id' => '2093', 'salary' => 30000,
            'gender' => 'male', 'date_of_birth' => '1990-02-14',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(12),
        ]);

        $this->createEmployee($company, $opsDept, $posOpsAssociate, $employeeRole, [
            'employee_id' => 'EMP-018', 'first_name' => 'Miriam', 'last_name' => 'Castillo',
            'alpeta_employee_id' => '2098', 'salary' => 29000,
            'gender' => 'female', 'date_of_birth' => '1994-11-03',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(8),
        ]);

        $this->createEmployee($company, $opsDept, $posOpsAssociate, $employeeRole, [
            'employee_id' => 'EMP-019', 'first_name' => 'Benjamin', 'last_name' => 'Flores',
            'alpeta_employee_id' => '2100', 'salary' => 28000,
            'gender' => 'male', 'date_of_birth' => '1992-08-19',
            'employment_type' => 'full_time', 'date_hired' => now()->subMonths(7),
        ]);

        $this->createEmployee($company, $opsDept, $posOpsAssociate, $employeeRole, [
            'employee_id' => 'EMP-020', 'first_name' => 'Teresita', 'last_name' => 'Domingo',
            'alpeta_employee_id' => '2111', 'salary' => 27000,
            'gender' => 'female', 'date_of_birth' => '1995-04-08',
            'employment_type' => 'probationary', 'date_hired' => now()->subMonths(4),
        ]);

        // Under Lorna Villanueva (EMP-008)
        $this->createEmployee($company, $opsDept, $posOpsAssociate, $employeeRole, [
            'employee_id' => 'EMP-021', 'first_name' => 'Andres', 'last_name' => 'Bautista',
            'alpeta_employee_id' => '2112', 'salary' => 26000,
            'gender' => 'male', 'date_of_birth' => '1993-06-26',
            'employment_type' => 'probationary', 'date_hired' => now()->subMonths(3),
        ]);

        $this->createEmployee($company, $opsDept, $posOpsAssociate, $employeeRole, [
            'employee_id' => 'EMP-022', 'first_name' => 'Grace', 'last_name' => 'Ocampo',
            'alpeta_employee_id' => '2123', 'salary' => 25000,
            'gender' => 'female', 'date_of_birth' => '1996-09-15',
            'employment_type' => 'probationary', 'date_hired' => now()->subMonths(2),
        ]);

        // --- Set department managers (after employees are created) ---
        $itDept->update(['manager_id' => $emp003->id]);
        $opsDept->update(['manager_id' => $emp004->id]);

        // --- Initialize leave balances for new employees ---
        $leaveTypes = LeaveType::where('company_id', $company->id)->get();
        $newEmployees = Employee::where('company_id', $company->id)
            ->whereNotIn('employee_id', ['EMP-001', 'EMP-002'])
            ->get();

        foreach ($newEmployees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                LeaveBalance::firstOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => now()->year],
                    ['total_days' => $leaveType->days_per_year, 'used_days' => 0, 'remaining_days' => $leaveType->days_per_year, 'carried_over_days' => 0]
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createEmployee(
        \App\Modules\Core\Models\Company $company,
        Department $department,
        Position $position,
        Role $role,
        array $data
    ): Employee {
        $firstName = $data['first_name'];
        $lastName = $data['last_name'];
        $email = strtolower(str_replace(' ', '', $firstName)).'.'.strtolower(str_replace(' ', '', $lastName)).'@testcompany.com';

        $user = User::create([
            'name' => "{$firstName} {$lastName}",
            'email' => $email,
            'password' => Hash::make('password'),
            'company_id' => $company->id,
            'email_verified_at' => now(),
        ]);

        $user->roles()->attach($role->id);

        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employee_id' => $data['employee_id'],
            'first_name' => $firstName,
            'middle_name' => '',
            'last_name' => $lastName,
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'email' => $email,
            'phone' => '09'.str_pad((string) rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            'address' => '123 Sample Street',
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'postal_code' => '1000',
            'date_hired' => $data['date_hired'] ?? now()->subMonths(rand(8, 36)),
            'employment_status' => 'active',
            'employment_type' => $data['employment_type'] ?? 'full_time',
            'salary' => $data['salary'],
            'salary_type' => 'monthly',
            'alpeta_employee_id' => $data['alpeta_employee_id'],
            'is_active' => true,
        ]);

        return $employee;
    }
}
