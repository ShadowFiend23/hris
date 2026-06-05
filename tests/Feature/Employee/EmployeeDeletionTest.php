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

class EmployeeDeletionTest extends TestCase
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
            'is_active' => true,
        ]);
    }

    public function test_can_soft_delete_employee(): void
    {
        $response = $this->actingAs($this->user)
            ->delete("/employees/{$this->employee->id}");

        $response->assertRedirect('/employees');
        $this->assertSoftDeleted('employees', ['id' => $this->employee->id]);
    }

    public function test_deleted_employee_is_marked_inactive(): void
    {
        $this->actingAs($this->user)
            ->delete("/employees/{$this->employee->id}");

        $this->employee->refresh();
        $this->assertFalse($this->employee->is_active);
    }

    public function test_deleted_employee_user_link_is_removed(): void
    {
        // Link user to employee
        $linkedUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->employee->user_id = $linkedUser->id;
        $this->employee->save();

        $this->actingAs($this->user)
            ->delete("/employees/{$this->employee->id}");

        $this->employee->refresh();
        $this->assertNull($this->employee->user_id);
    }

    public function test_guest_cannot_delete_employee(): void
    {
        $response = $this->delete("/employees/{$this->employee->id}");

        $response->assertRedirect('/login');
        $this->assertNotSoftDeleted('employees', ['id' => $this->employee->id]);
    }

    public function test_employee_not_permanently_deleted(): void
    {
        $this->actingAs($this->user)
            ->delete("/employees/{$this->employee->id}");

        // Employee should still exist in database (soft deleted)
        $this->assertDatabaseHas('employees', ['id' => $this->employee->id]);

        // But should be soft deleted
        $deletedEmployee = Employee::withTrashed()->find($this->employee->id);
        $this->assertNotNull($deletedEmployee->deleted_at);
    }

    public function test_deleted_employee_not_in_regular_queries(): void
    {
        $this->actingAs($this->user)
            ->delete("/employees/{$this->employee->id}");

        $employees = Employee::where('company_id', $this->company->id)->get();
        $this->assertFalse($employees->contains('id', $this->employee->id));
    }
}
