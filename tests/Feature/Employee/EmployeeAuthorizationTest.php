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

class EmployeeAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    protected User $user;

    protected User $otherUser;

    protected Company $company;

    protected Company $otherCompany;

    protected Department $department;

    protected Position $position;

    protected Employee $employee;

    protected Employee $otherEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up first company
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
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Set up second company
        $this->otherCompany = Company::factory()->create();
        $this->setupModuleAccess($this->otherCompany->id);
        $this->otherUser = User::factory()->create(['company_id' => $this->otherCompany->id]);
        $otherDepartment = Department::create([
            'company_id' => $this->otherCompany->id,
            'name' => 'Sales',
            'slug' => 'sales',
            'is_active' => true,
        ]);
        $otherPosition = Position::create([
            'department_id' => $otherDepartment->id,
            'position_name' => 'Sales Rep',
            'is_active' => true,
        ]);
        $this->otherEmployee = Employee::factory()->create([
            'company_id' => $this->otherCompany->id,
            'department_id' => $otherDepartment->id,
            'position_id' => $otherPosition->id,
        ]);
    }

    public function test_user_can_view_own_company_employees(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/employees');

        $response->assertOk();
    }

    public function test_user_can_view_own_company_employee_detail(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/employees/{$this->employee->id}");

        $response->assertOk();
    }

    public function test_user_cannot_view_other_company_employee(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/employees/{$this->otherEmployee->id}");

        $response->assertForbidden();
    }

    public function test_user_cannot_edit_other_company_employee(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/employees/{$this->otherEmployee->id}/edit");

        $response->assertForbidden();
    }

    public function test_user_cannot_update_other_company_employee(): void
    {
        $response = $this->actingAs($this->user)
            ->put("/employees/{$this->otherEmployee->id}", [
                'first_name' => 'Hacked',
                'last_name' => 'Name',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('employees', [
            'id' => $this->otherEmployee->id,
            'first_name' => 'Hacked',
        ]);
    }

    public function test_user_cannot_delete_other_company_employee(): void
    {
        $response = $this->actingAs($this->user)
            ->delete("/employees/{$this->otherEmployee->id}");

        $response->assertForbidden();
        $this->assertNotSoftDeleted('employees', ['id' => $this->otherEmployee->id]);
    }

    public function test_employee_list_only_shows_own_company_employees(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/employees');

        $response->assertOk();
        // The response should contain own company employee but not other company employee
        $response->assertInertia(fn ($page) => $page
            ->has('employees.data')
            ->where('employees.data', function ($data) {
                // All employees should belong to user's company
                foreach ($data as $emp) {
                    if ((int) $emp['company_id'] !== $this->company->id) {
                        return false;
                    }
                }

                return true;
            })
        );
    }

    public function test_user_can_only_select_own_company_departments(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/employees/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('departments')
            ->where('departments', function ($departments) {
                foreach ($departments as $dept) {
                    if ((int) $dept['id'] !== $this->department->id) {
                        return false;
                    }
                }

                return true;
            })
        );
    }
}
