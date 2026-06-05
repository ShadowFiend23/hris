<?php

namespace Tests\Feature\Employee;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class EmployeeCreationTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    protected User $user;

    protected Company $company;

    protected Department $department;

    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Engineering',
            'slug' => 'engineering',
            'is_active' => true,
        ]);
        $this->position = Position::create([
            'department_id' => $this->department->id,
            'position_name' => 'Software Engineer',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_create_employee(): void
    {
        $employeeData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'date_hired' => now()->subMonth()->format('Y-m-d'),
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ];

        $response = $this->actingAs($this->user)
            ->post('/employees', $employeeData);

        $response->assertRedirect('/employees');
        $this->assertDatabaseHas('employees', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_employee_id_is_auto_generated_if_not_provided(): void
    {
        $employeeData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'date_hired' => now()->subMonth()->format('Y-m-d'),
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ];

        $this->actingAs($this->user)->post('/employees', $employeeData);

        $employee = Employee::where('email', 'jane.smith@example.com')->first();
        $this->assertNotNull($employee->employee_id);
        $this->assertMatchesRegularExpression('/^EMP-\d{4}$/', $employee->employee_id);
    }

    public function test_cannot_create_employee_with_duplicate_email(): void
    {
        Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@example.com',
        ]);

        $employeeData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'date_hired' => now()->subMonth()->format('Y-m-d'),
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ];

        $response = $this->actingAs($this->user)
            ->post('/employees', $employeeData);

        $response->assertSessionHasErrors('email');
    }

    public function test_cannot_create_employee_without_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', []);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'date_hired', 'department_id', 'position_id']);
    }

    public function test_guest_cannot_create_employee(): void
    {
        $response = $this->post('/employees', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'date_hired' => now()->format('Y-m-d'),
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_can_create_employee_with_all_optional_fields(): void
    {
        $employeeData = [
            'employee_id' => 'EMP-9999',
            'first_name' => 'Complete',
            'middle_name' => 'Middle',
            'last_name' => 'Employee',
            'email' => 'complete@example.com',
            'phone' => '+639123456789',
            'date_of_birth' => '1990-01-15',
            'gender' => 'male',
            'address' => '123 Main Street',
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'postal_code' => '1000',
            'date_hired' => now()->subMonth()->format('Y-m-d'),
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'salary' => 50000.00,
            'bank_account' => '1234567890',
            'tin' => '123-456-789-000',
            'sss_number' => '12-3456789-0',
            'philhealth_number' => '12-345678901-2',
            'pagibig_number' => '1234-5678-9012',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ];

        $response = $this->actingAs($this->user)
            ->post('/employees', $employeeData);

        $response->assertRedirect('/employees');
        $this->assertDatabaseHas('employees', [
            'employee_id' => 'EMP-9999',
            'first_name' => 'Complete',
            'middle_name' => 'Middle',
            'last_name' => 'Employee',
            'tin' => '123-456-789-000',
        ]);
    }
}
