<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;
use App\Modules\Core\Models\Position;
use App\Modules\Core\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles and permissions
        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'is_system' => true,
        ]);

        Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'is_system' => true,
        ]);

        Role::create([
            'name' => 'Employee',
            'slug' => 'employee',
            'is_system' => true,
        ]);

        // Call ModuleSeeder to create default modules
        $this->call(ModuleSeeder::class);

        // Seed permissions and assign to roles
        $this->call(PermissionSeeder::class);

        // Create a test company
        $company = Company::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'registration_number' => 'REG123456',
            'address' => '123 Test Street',
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'postal_code' => '1000',
            'phone' => '(02) 1234-5678',
            'email' => 'info@testcompany.com',
            'website' => 'https://testcompany.com',
            'industry' => 'Information Technology',
            'employee_count' => 50,
            'is_active' => true,
        ]);

        // Create a default license for the test company with all modules enabled
        $license = License::create([
            'company_id' => $company->id,
            'license_key' => 'LIC-'.Str::random(12),
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now(),
            'valid_until' => now()->addYear(),
            'user_limit' => 999,
            'notes' => 'Default license created at company setup',
        ]);

        // Attach all three modules to the license
        $modules = Module::where('is_active', true)->pluck('id');
        $license->modules()->attach($modules);

        // Create a test department
        $department = Department::create([
            'company_id' => $company->id,
            'name' => 'Human Resources',
            'slug' => 'human-resources',
            'description' => 'HR Department',
            'is_active' => true,
        ]);

        // Create a test position
        $position = Position::create([
            'department_id' => $department->id,
            'position_name' => 'HR Manager',
            'is_active' => true,
        ]);

        // Create a second position
        $position2 = Position::create([
            'department_id' => $department->id,
            'position_name' => 'HR Specialist',
            'is_active' => true,
        ]);

        // Create test user 1
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'email_verified_at' => now(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        // Assign role to user
        $user->roles()->attach($adminRole);

        // Create test user 2 (admin2)
        $user2 = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'email_verified_at' => now(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        // Assign role to user2
        $user2->roles()->attach($adminRole);

        // Create an employee record for the test user
        Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employee_id' => 'EMP-001',
            'first_name' => 'Test',
            'middle_name' => '',
            'last_name' => 'User',
            'date_of_birth' => '1990-01-15',
            'gender' => 'male',
            'email' => 'test@example.com',
            'phone' => '09171234567',
            'address' => '123 Test Street',
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'postal_code' => '1000',
            'date_hired' => now()->subYear(),
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'salary' => 50000,
            'bank_account' => '1234567890123456',
            'tin' => '123-456-789',
            'sss_number' => '12-3456789-0',
            'philhealth_number' => '123456789012',
            'pagibig_number' => '1234567890123456',
            'is_active' => true,
        ]);

        // Create an employee record for admin user
        Employee::create([
            'user_id' => $user2->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position2->id,
            'employee_id' => 'EMP-002',
            'first_name' => 'Admin',
            'middle_name' => '',
            'last_name' => 'User',
            'date_of_birth' => '1988-05-20',
            'gender' => 'female',
            'email' => 'admin@example.com',
            'phone' => '09179876543',
            'address' => '456 Admin Street',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'postal_code' => '1200',
            'date_hired' => now()->subMonths(18),
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'salary' => 60000,
            'bank_account' => '9876543210123456',
            'tin' => '987-654-321',
            'sss_number' => '98-7654321-0',
            'philhealth_number' => '987654321012',
            'pagibig_number' => '9876543210123456',
            'is_active' => true,
        ]);

        // Call TimekeepingSeeder to create leave types, shift templates, etc.
        $this->call(TimekeepingSeeder::class);

        // Seed 20 employees with Alpeta IDs and org hierarchy
        $this->call(EmployeeSeeder::class);

        // Seed Philippine national holidays
        $this->call(PHHolidaySeeder::class);

        // Seed SSS / PhilHealth / Pag-IBIG contribution brackets
        $this->call(ContributionBracketSeeder::class);

        // Seed biometric terminal IN/OUT mappings
        $this->call(BiometricTerminalSeeder::class);

        // Seed default payroll settings (semi-monthly)
        $this->call(PayrollSettingSeeder::class);

        // Seed allowance types and employee allowances
        $this->call(AllowanceTypeSeeder::class);
        $this->call(EmployeeAllowanceSeeder::class);

        // Seed default loan types
        $this->call(LoanTypeSeeder::class);
    }
}
