<?php

namespace Tests\Feature\EmployeeSettings;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Position;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class EmployeeSettingsTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    private User $admin;

    private User $nonAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id, ['hris']);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'slug' => 'admin', 'is_system' => true]);
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'slug' => 'staff', 'is_system' => false]);

        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin->roles()->attach($adminRole->id);
        Employee::factory()->create(['user_id' => $this->admin->id, 'company_id' => $this->company->id]);

        $this->nonAdmin = User::factory()->create(['company_id' => $this->company->id]);
        $this->nonAdmin->roles()->attach($staffRole->id);
        Employee::factory()->create(['user_id' => $this->nonAdmin->id, 'company_id' => $this->company->id]);

        $this->admin = $this->admin->fresh('roles.permissions');
        $this->nonAdmin = $this->nonAdmin->fresh('roles.permissions');
    }

    public function test_settings_page_renders_with_departments_and_positions(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        Position::factory()->create(['department_id' => $dept->id]);

        $this->actingAs($this->admin)
            ->get('/hr-settings/employee-settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRSettings/EmployeeSettings')
                ->has('departments')
                ->has('positions')
            );
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $this->actingAs($this->nonAdmin)
            ->get('/hr-settings/employee-settings')
            ->assertForbidden();
    }

    public function test_unauthenticated_redirected_to_login(): void
    {
        $this->get('/hr-settings/employee-settings')->assertRedirect('/login');
    }

    public function test_admin_can_create_department(): void
    {
        $this->actingAs($this->admin)
            ->post('/hr-settings/departments', [
                'name' => 'Engineering',
                'description' => 'Software engineering team',
            ])
            ->assertRedirect('/hr-settings/employee-settings');

        $this->assertDatabaseHas('departments', [
            'company_id' => $this->company->id,
            'name' => 'Engineering',
            'slug' => 'engineering',
        ]);
    }

    public function test_non_admin_cannot_create_department(): void
    {
        $this->actingAs($this->nonAdmin)
            ->post('/hr-settings/departments', ['name' => 'Engineering'])
            ->assertForbidden();
    }

    public function test_admin_can_update_department(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id, 'name' => 'Old Name']);

        $this->actingAs($this->admin)
            ->put("/hr-settings/departments/{$dept->id}", [
                'name' => 'New Name',
                'description' => 'Updated description',
            ])
            ->assertRedirect('/hr-settings/employee-settings');

        $this->assertEquals('New Name', $dept->fresh()->name);
    }

    public function test_admin_cannot_update_other_company_department(): void
    {
        $otherCompany = Company::factory()->create();
        $dept = Department::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($this->admin)
            ->put("/hr-settings/departments/{$dept->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_admin_can_toggle_department_status(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);

        $this->actingAs($this->admin)
            ->patch("/hr-settings/departments/{$dept->id}/toggle")
            ->assertRedirect('/hr-settings/employee-settings');

        $this->assertFalse($dept->fresh()->is_active);
    }

    public function test_admin_can_delete_empty_department(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->admin)
            ->delete("/hr-settings/departments/{$dept->id}")
            ->assertRedirect('/hr-settings/employee-settings');

        $this->assertSoftDeleted('departments', ['id' => $dept->id]);
    }

    public function test_cannot_delete_department_with_employees(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $pos = Position::factory()->create(['department_id' => $dept->id]);
        Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
        ]);

        $this->actingAs($this->admin)
            ->delete("/hr-settings/departments/{$dept->id}")
            ->assertRedirect('/hr-settings/employee-settings');

        $this->assertDatabaseHas('departments', ['id' => $dept->id, 'deleted_at' => null]);
    }

    public function test_admin_can_create_position(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->admin)
            ->post('/hr-settings/positions', [
                'position_name' => 'Senior Engineer',
                'department_id' => $dept->id,
                'reports_to_position_id' => null,
            ])
            ->assertRedirect('/hr-settings/employee-settings');

        $this->assertDatabaseHas('positions', [
            'department_id' => $dept->id,
            'position_name' => 'Senior Engineer',
        ]);
    }

    public function test_position_can_have_reporting_hierarchy(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $manager = Position::factory()->create(['department_id' => $dept->id, 'position_name' => 'Manager']);

        $this->actingAs($this->admin)
            ->post('/hr-settings/positions', [
                'position_name' => 'Engineer',
                'department_id' => $dept->id,
                'reports_to_position_id' => $manager->id,
            ])
            ->assertRedirect('/hr-settings/employee-settings');

        $position = Position::where('position_name', 'Engineer')->first();
        $this->assertEquals($manager->id, $position->reports_to_position_id);
    }

    public function test_circular_reference_is_rejected(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $posA = Position::factory()->create(['department_id' => $dept->id]);
        $posB = Position::factory()->create(['department_id' => $dept->id, 'reports_to_position_id' => $posA->id]);

        // Try to make A report to B — that would create A → B → A cycle
        $this->actingAs($this->admin)
            ->put("/hr-settings/positions/{$posA->id}", [
                'position_name' => $posA->position_name,
                'department_id' => $dept->id,
                'reports_to_position_id' => $posB->id,
            ])
            ->assertSessionHasErrors('reports_to_position_id');
    }

    public function test_position_cannot_report_to_itself(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $pos = Position::factory()->create(['department_id' => $dept->id]);

        $this->actingAs($this->admin)
            ->put("/hr-settings/positions/{$pos->id}", [
                'position_name' => $pos->position_name,
                'department_id' => $dept->id,
                'reports_to_position_id' => $pos->id,
            ])
            ->assertSessionHasErrors('reports_to_position_id');
    }

    public function test_admin_can_toggle_position_status(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $pos = Position::factory()->create(['department_id' => $dept->id, 'is_active' => true]);

        $this->actingAs($this->admin)
            ->patch("/hr-settings/positions/{$pos->id}/toggle")
            ->assertRedirect('/hr-settings/employee-settings');

        $this->assertFalse($pos->fresh()->is_active);
    }

    public function test_admin_can_delete_empty_position(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $pos = Position::factory()->create(['department_id' => $dept->id]);

        $this->actingAs($this->admin)
            ->delete("/hr-settings/positions/{$pos->id}")
            ->assertRedirect('/hr-settings/employee-settings');

        $this->assertSoftDeleted('positions', ['id' => $pos->id]);
    }

    public function test_cannot_delete_position_with_employees(): void
    {
        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $pos = Position::factory()->create(['department_id' => $dept->id]);
        Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
        ]);

        $this->actingAs($this->admin)
            ->delete("/hr-settings/positions/{$pos->id}")
            ->assertRedirect('/hr-settings/employee-settings');

        $this->assertDatabaseHas('positions', ['id' => $pos->id, 'deleted_at' => null]);
    }

    public function test_page_only_returns_own_company_departments(): void
    {
        $otherCompany = Company::factory()->create();
        Department::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Other Corp Dept']);
        Department::factory()->create(['company_id' => $this->company->id, 'name' => 'My Dept']);

        $this->actingAs($this->admin)
            ->get('/hr-settings/employee-settings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('departments', fn ($depts) => collect($depts)->every(fn ($d) => $d['name'] !== 'Other Corp Dept'))
            );
    }
}
