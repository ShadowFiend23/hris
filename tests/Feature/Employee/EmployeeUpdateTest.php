<?php

namespace Tests\Feature\Employee;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Position;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class EmployeeUpdateTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    protected User $user;

    protected Company $company;

    protected Department $department;

    protected Position $position;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->grantEmployeeEditPermission($this->user);
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
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);
    }

    /**
     * Grant the user the permission required by EmployeePolicy::update.
     */
    private function grantEmployeeEditPermission(User $user): void
    {
        $role = Role::create(['name' => 'Employee Editor', 'slug' => 'employee-editor-'.uniqid()]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'hris.employees.edit'],
            ['name' => 'Edit Employees', 'slug' => 'hris.employees.edit', 'group' => 'hris']
        );
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);
        $user->load('roles.permissions');
    }

    public function test_can_update_employee_basic_info(): void
    {
        $response = $this->actingAs($this->user)
            ->put("/employees/{$this->employee->id}", [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'email' => 'updated@example.com',
                'date_hired' => $this->employee->date_hired->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertRedirect("/employees/{$this->employee->id}");
        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_can_update_employee_with_same_email(): void
    {
        $response = $this->actingAs($this->user)
            ->put("/employees/{$this->employee->id}", [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'email' => $this->employee->email, // Same email
                'date_hired' => $this->employee->date_hired->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionDoesntHaveErrors('email');
    }

    public function test_cannot_update_employee_with_existing_email(): void
    {
        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'other@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->put("/employees/{$this->employee->id}", [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'email' => 'other@example.com', // Existing email from another employee
                'date_hired' => $this->employee->date_hired->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_can_update_employee_salary(): void
    {
        $response = $this->actingAs($this->user)
            ->put("/employees/{$this->employee->id}", [
                'first_name' => $this->employee->first_name,
                'last_name' => $this->employee->last_name,
                'email' => $this->employee->email,
                'date_hired' => $this->employee->date_hired->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'salary' => 75000.50,
            ]);

        $response->assertRedirect();
        $this->employee->refresh();
        $this->assertEquals(75000.50, $this->employee->salary);
    }

    public function test_can_update_employee_status(): void
    {
        $response = $this->actingAs($this->user)
            ->put("/employees/{$this->employee->id}", [
                'first_name' => $this->employee->first_name,
                'last_name' => $this->employee->last_name,
                'email' => $this->employee->email,
                'date_hired' => $this->employee->date_hired->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'employment_status' => 'inactive',
            ]);

        $response->assertRedirect();
        $this->employee->refresh();
        $this->assertEquals('inactive', $this->employee->employment_status);
    }

    public function test_guest_cannot_update_employee(): void
    {
        $response = $this->put("/employees/{$this->employee->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_can_update_government_ids(): void
    {
        $response = $this->actingAs($this->user)
            ->put("/employees/{$this->employee->id}", [
                'first_name' => $this->employee->first_name,
                'last_name' => $this->employee->last_name,
                'email' => $this->employee->email,
                'date_hired' => $this->employee->date_hired->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'tin' => '999-888-777-000',
                'sss_number' => '99-8888888-7',
                'philhealth_number' => '99-888888888-7',
                'pagibig_number' => '9999-8888-7777',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'tin' => '999-888-777-000',
            'sss_number' => '99-8888888-7',
        ]);
    }
}
