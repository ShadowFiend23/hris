<?php

namespace Tests\Feature\Timekeeping;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Role;
use App\Modules\Timekeeping\Models\ScheduleChangeRequest;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class ScheduleChangeTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    private User $adminUser;

    private Employee $adminEmployee;

    private User $staffUser;

    private Employee $staffEmployee;

    private ShiftTemplate $shiftTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id, ['timekeeping']);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'slug' => 'admin', 'is_system' => true]);
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'slug' => 'staff', 'is_system' => false]);

        $this->adminUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->adminUser->roles()->attach($adminRole->id);
        $this->adminEmployee = Employee::factory()->create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
        ]);

        $this->staffUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->staffUser->roles()->attach($staffRole->id);
        $this->staffEmployee = Employee::factory()->create([
            'user_id' => $this->staffUser->id,
            'company_id' => $this->company->id,
        ]);

        $this->shiftTemplate = ShiftTemplate::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Evening Shift',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
        ]);

        $this->adminUser = $this->adminUser->fresh('roles.permissions');
        $this->staffUser = $this->staffUser->fresh('roles.permissions');
    }

    public function test_employee_can_list_available_shift_templates(): void
    {
        $this->actingAs($this->staffUser)
            ->getJson('/api/timekeeping/schedule-change/templates')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'start_time', 'end_time']]]);
    }

    public function test_employee_can_submit_schedule_change_request(): void
    {
        $date = Carbon::tomorrow()->toDateString();

        $this->actingAs($this->staffUser)
            ->postJson('/api/timekeeping/schedule-change', [
                'requested_shift_template_id' => $this->shiftTemplate->id,
                'date' => $date,
                'reason' => 'Family event on this day.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertTrue(
            ScheduleChangeRequest::where('employee_id', $this->staffEmployee->id)
                ->where('requested_shift_template_id', $this->shiftTemplate->id)
                ->whereDate('date', $date)
                ->where('status', 'pending')
                ->exists(),
            'Expected a pending schedule change request to exist for the given date.'
        );
    }

    public function test_employee_cannot_submit_request_for_past_date(): void
    {
        $this->actingAs($this->staffUser)
            ->postJson('/api/timekeeping/schedule-change', [
                'requested_shift_template_id' => $this->shiftTemplate->id,
                'date' => Carbon::yesterday()->toDateString(),
                'reason' => 'Test reason.',
            ])
            ->assertUnprocessable();
    }

    public function test_employee_cannot_submit_duplicate_pending_request_for_same_date(): void
    {
        $date = Carbon::tomorrow()->toDateString();

        ScheduleChangeRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->staffEmployee->id,
            'requested_shift_template_id' => $this->shiftTemplate->id,
            'date' => $date,
            'reason' => 'Existing request.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->staffUser)
            ->postJson('/api/timekeeping/schedule-change', [
                'requested_shift_template_id' => $this->shiftTemplate->id,
                'date' => $date,
                'reason' => 'Another reason.',
            ])
            ->assertStatus(422);
    }

    public function test_employee_can_view_own_requests(): void
    {
        ScheduleChangeRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->staffEmployee->id,
            'requested_shift_template_id' => $this->shiftTemplate->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'reason' => 'Need a different shift.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->staffUser)
            ->getJson('/api/timekeeping/schedule-change')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_employee_can_cancel_own_pending_request(): void
    {
        $request = ScheduleChangeRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->staffEmployee->id,
            'requested_shift_template_id' => $this->shiftTemplate->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'reason' => 'Need a different shift.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->staffUser)
            ->postJson("/api/timekeeping/schedule-change/{$request->id}/cancel")
            ->assertOk();

        $this->assertDatabaseHas('schedule_change_requests', [
            'id' => $request->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_employee_cannot_cancel_another_employees_request(): void
    {
        $otherEmployee = Employee::factory()->create(['company_id' => $this->company->id]);
        $request = ScheduleChangeRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
            'requested_shift_template_id' => $this->shiftTemplate->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'reason' => 'Need a different shift.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->staffUser)
            ->postJson("/api/timekeeping/schedule-change/{$request->id}/cancel")
            ->assertForbidden();
    }

    public function test_admin_can_view_pending_requests(): void
    {
        ScheduleChangeRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->staffEmployee->id,
            'requested_shift_template_id' => $this->shiftTemplate->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'reason' => 'Need a different shift.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser)
            ->getJson('/api/timekeeping/schedule-change/pending')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_approve_request_and_schedule_is_created(): void
    {
        $date = Carbon::tomorrow()->toDateString();

        $request = ScheduleChangeRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->staffEmployee->id,
            'requested_shift_template_id' => $this->shiftTemplate->id,
            'date' => $date,
            'reason' => 'Need a different shift.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser)
            ->postJson("/api/timekeeping/schedule-change/{$request->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('schedule_change_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'approved_by' => $this->adminUser->id,
        ]);

        $this->assertTrue(
            \App\Modules\Timekeeping\Models\EmployeeSchedule::where('employee_id', $this->staffEmployee->id)
                ->where('shift_template_id', $this->shiftTemplate->id)
                ->whereDate('date', $date)
                ->exists(),
            'Expected an employee schedule record to be created for the approved date.'
        );
    }

    public function test_admin_can_reject_request_with_reason(): void
    {
        $request = ScheduleChangeRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->staffEmployee->id,
            'requested_shift_template_id' => $this->shiftTemplate->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'reason' => 'Need a different shift.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser)
            ->postJson("/api/timekeeping/schedule-change/{$request->id}/reject", [
                'rejection_reason' => 'Staffing constraints on that day.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('schedule_change_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejection_reason' => 'Staffing constraints on that day.',
        ]);
    }

    public function test_reject_requires_rejection_reason(): void
    {
        $request = ScheduleChangeRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->staffEmployee->id,
            'requested_shift_template_id' => $this->shiftTemplate->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'reason' => 'Need a different shift.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser)
            ->postJson("/api/timekeeping/schedule-change/{$request->id}/reject", [])
            ->assertUnprocessable();
    }

    public function test_cannot_approve_already_approved_request(): void
    {
        $request = ScheduleChangeRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->staffEmployee->id,
            'requested_shift_template_id' => $this->shiftTemplate->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'reason' => 'Need a different shift.',
            'status' => 'approved',
        ]);

        $this->actingAs($this->adminUser)
            ->postJson("/api/timekeeping/schedule-change/{$request->id}/approve")
            ->assertStatus(400);
    }

    public function test_settings_page_exposes_schedule_change_approval_steps(): void
    {
        $this->withoutVite();

        $this->actingAs($this->adminUser)
            ->get('/app-settings/timekeeping-settings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('scheduleChangeApprovalSteps'));
    }

    public function test_admin_can_save_schedule_change_approval_chain(): void
    {
        $this->withoutVite();

        $role = Role::firstOrCreate(['slug' => 'hr_manager'], ['name' => 'HR Manager', 'slug' => 'hr_manager', 'is_system' => false]);

        $this->actingAs($this->adminUser)
            ->post('/app-settings/timekeeping/approval-chain', [
                'type' => 'schedule_change',
                'steps' => [
                    ['order' => 1, 'role_id' => $role->id],
                ],
            ])
            ->assertRedirect('/app-settings/timekeeping-settings');

        $this->assertDatabaseHas('timekeeping_approval_settings', [
            'company_id' => $this->company->id,
            'type' => 'schedule_change',
        ]);
    }
}
