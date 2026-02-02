<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;
use App\Modules\Core\Models\Permission;
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
            'license_key' => 'LIC-' . Str::random(12),
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

        // Create a test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'email_verified_at' => now(),
        ]);

        // Assign role to user
        $user->roles()->attach($adminRole);

        // Create an employee record for the test user
        Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
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
            'employment_type' => 'Full-time',
            'position_id' => '1',
            'salary' => 50000,
            'bank_account' => '1234567890123456',
            'tin' => '123-456-789',
            'sss_number' => '12-3456789-0',
            'philhealth_number' => '123456789012',
            'pagibig_number' => '1234567890123456',
            'is_active' => true,
        ]);
    }
}
