<?php

namespace Tests\Feature\Employee;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Position;
use App\Modules\Core\Models\Role;
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

    public function test_manager_only_sees_direct_reports_in_employee_list(): void
    {
        $managerRole = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $managerUser = User::factory()->create(['company_id' => $this->company->id]);
        $managerUser->roles()->attach($managerRole);

        $managerEmployee = Employee::factory()->create([
            'user_id' => $managerUser->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $directReport = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => $managerEmployee->id,
        ]);

        $nonReport = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => null,
        ]);

        $response = $this->actingAs($managerUser)->get('/employees');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('employees.data')
            ->where('employees.data', function ($data) use ($directReport, $nonReport) {
                $ids = collect($data)->pluck('id')->all();

                return in_array($directReport->id, $ids) && ! in_array($nonReport->id, $ids);
            })
        );
    }

    public function test_manager_can_view_direct_report_detail(): void
    {
        $managerRole = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $managerUser = User::factory()->create(['company_id' => $this->company->id]);
        $managerUser->roles()->attach($managerRole);

        $managerEmployee = Employee::factory()->create([
            'user_id' => $managerUser->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $directReport = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => $managerEmployee->id,
        ]);

        $response = $this->actingAs($managerUser)->get("/employees/{$directReport->id}");

        $response->assertOk();
    }

    public function test_manager_cannot_view_non_direct_report_detail(): void
    {
        $managerRole = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $managerUser = User::factory()->create(['company_id' => $this->company->id]);
        $managerUser->roles()->attach($managerRole);

        Employee::factory()->create([
            'user_id' => $managerUser->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $unrelatedEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => null,
        ]);

        $response = $this->actingAs($managerUser)->get("/employees/{$unrelatedEmployee->id}");

        $response->assertForbidden();
    }

    public function test_director_sees_indirect_reports_in_employee_list(): void
    {
        $managerRole = Role::create(['name' => 'Manager', 'slug' => 'manager']);

        // Director
        $directorUser = User::factory()->create(['company_id' => $this->company->id]);
        $directorUser->roles()->attach($managerRole);
        $director = Employee::factory()->create([
            'user_id' => $directorUser->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Team lead reports to director
        $teamLead = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => $director->id,
        ]);

        // Developer reports to team lead (indirect report of director)
        $developer = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => $teamLead->id,
        ]);

        $response = $this->actingAs($directorUser)->get('/employees');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('employees.data')
            ->where('employees.data', function ($data) use ($teamLead, $developer) {
                $ids = collect($data)->pluck('id')->all();

                return in_array($teamLead->id, $ids) && in_array($developer->id, $ids);
            })
        );
    }

    public function test_manager_cannot_edit_direct_report(): void
    {
        $managerRole = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $managerUser = User::factory()->create(['company_id' => $this->company->id]);
        $managerUser->roles()->attach($managerRole);

        $managerEmployee = Employee::factory()->create([
            'user_id' => $managerUser->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $directReport = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => $managerEmployee->id,
        ]);

        $response = $this->actingAs($managerUser)->get("/employees/{$directReport->id}/edit");

        $response->assertForbidden();
    }

    public function test_manager_cannot_update_direct_report(): void
    {
        $managerRole = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $managerUser = User::factory()->create(['company_id' => $this->company->id]);
        $managerUser->roles()->attach($managerRole);

        $managerEmployee = Employee::factory()->create([
            'user_id' => $managerUser->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $directReport = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => $managerEmployee->id,
        ]);

        $originalName = $directReport->first_name;

        $response = $this->actingAs($managerUser)->put("/employees/{$directReport->id}", [
            'first_name' => 'Changed',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('employees', [
            'id' => $directReport->id,
            'first_name' => $originalName,
        ]);
    }

    public function test_manager_index_page_returns_can_edit_false(): void
    {
        $managerRole = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $managerUser = User::factory()->create(['company_id' => $this->company->id]);
        $managerUser->roles()->attach($managerRole);

        Employee::factory()->create([
            'user_id' => $managerUser->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $response = $this->actingAs($managerUser)->get('/employees');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('canEdit', false)
            ->where('canDelete', false)
        );
    }

    public function test_team_lead_cannot_see_peer_team_leads_reports(): void
    {
        $managerRole = Role::create(['name' => 'Manager', 'slug' => 'manager']);

        // Director
        $directorEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Team lead A
        $leadAUser = User::factory()->create(['company_id' => $this->company->id]);
        $leadAUser->roles()->attach($managerRole);
        $leadA = Employee::factory()->create([
            'user_id' => $leadAUser->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => $directorEmployee->id,
        ]);

        // Team lead B (peer of lead A)
        $leadB = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => $directorEmployee->id,
        ]);

        // Developer under lead B — lead A must not see them
        $developerUnderB = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'supervisor_id' => $leadB->id,
        ]);

        $response = $this->actingAs($leadAUser)->get("/employees/{$developerUnderB->id}");

        $response->assertForbidden();
    }
}
