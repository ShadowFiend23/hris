<?php

namespace Tests\Feature\Timekeeping;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Role;
use App\Modules\Timekeeping\Models\TimekeepingApprovalSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class TimekeepingSettingsTest extends TestCase
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

        $this->company = Company::factory()->create(['leave_enabled' => true, 'ot_enabled' => true]);
        $this->setupModuleAccess($this->company->id, ['timekeeping']);

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

    public function test_settings_page_renders_with_props(): void
    {
        $this->actingAs($this->admin)
            ->get('/app-settings/leave-types')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRSettings/LeaveTypes')
                ->has('leaveEnabled')
                ->has('otEnabled')
                ->has('leaveApprovalSteps')
                ->has('otApprovalSteps')
                ->has('roles')
                ->where('leaveEnabled', true)
                ->where('otEnabled', true)
            );
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $this->actingAs($this->nonAdmin)
            ->get('/app-settings/leave-types')
            ->assertForbidden();
    }

    public function test_unauthenticated_redirected_to_login(): void
    {
        $this->get('/app-settings/leave-types')->assertRedirect('/login');
    }

    public function test_toggle_leave_disables_leave(): void
    {
        $this->actingAs($this->admin)
            ->patch('/app-settings/timekeeping/toggle-leave')
            ->assertRedirect('/app-settings/leave-types');

        $this->assertFalse($this->company->fresh()->leave_enabled);
    }

    public function test_toggle_leave_enables_leave_when_disabled(): void
    {
        $this->company->update(['leave_enabled' => false]);

        $this->actingAs($this->admin)
            ->patch('/app-settings/timekeeping/toggle-leave')
            ->assertRedirect('/app-settings/leave-types');

        $this->assertTrue($this->company->fresh()->leave_enabled);
    }

    public function test_toggle_ot_disables_ot(): void
    {
        $this->actingAs($this->admin)
            ->patch('/app-settings/timekeeping/toggle-ot')
            ->assertRedirect('/app-settings/leave-types');

        $this->assertFalse($this->company->fresh()->ot_enabled);
    }

    public function test_toggle_ot_enables_ot_when_disabled(): void
    {
        $this->company->update(['ot_enabled' => false]);

        $this->actingAs($this->admin)
            ->patch('/app-settings/timekeeping/toggle-ot')
            ->assertRedirect('/app-settings/leave-types');

        $this->assertTrue($this->company->fresh()->ot_enabled);
    }

    public function test_non_admin_cannot_toggle_leave(): void
    {
        $this->actingAs($this->nonAdmin)
            ->patch('/app-settings/timekeeping/toggle-leave')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_toggle_ot(): void
    {
        $this->actingAs($this->nonAdmin)
            ->patch('/app-settings/timekeeping/toggle-ot')
            ->assertForbidden();
    }

    public function test_save_leave_approval_chain_creates_setting(): void
    {
        $role = Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Manager', 'slug' => 'manager', 'is_system' => true]);

        $this->actingAs($this->admin)
            ->post('/app-settings/timekeeping/approval-chain', [
                'type' => 'leave',
                'steps' => [
                    ['order' => 1, 'role_id' => $role->id],
                ],
            ])
            ->assertRedirect('/app-settings/leave-types');

        $this->assertDatabaseHas('timekeeping_approval_settings', [
            'company_id' => $this->company->id,
            'type' => 'leave',
        ]);

        $setting = TimekeepingApprovalSetting::where('company_id', $this->company->id)
            ->where('type', 'leave')
            ->first();

        $this->assertCount(1, $setting->steps);
        $this->assertEquals($role->id, $setting->steps[0]['role_id']);
    }

    public function test_save_ot_approval_chain_with_multiple_steps(): void
    {
        $manager = Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Manager', 'slug' => 'manager', 'is_system' => true]);
        $director = Role::firstOrCreate(['slug' => 'director'], ['name' => 'Director', 'slug' => 'director', 'is_system' => true]);
        $admin = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'slug' => 'admin', 'is_system' => true]);

        $this->actingAs($this->admin)
            ->post('/app-settings/timekeeping/approval-chain', [
                'type' => 'ot',
                'steps' => [
                    ['order' => 1, 'role_id' => $manager->id],
                    ['order' => 2, 'role_id' => $director->id],
                    ['order' => 3, 'role_id' => $admin->id],
                ],
            ])
            ->assertRedirect('/app-settings/leave-types');

        $setting = TimekeepingApprovalSetting::where('company_id', $this->company->id)
            ->where('type', 'ot')
            ->first();

        $this->assertCount(3, $setting->steps);
    }

    public function test_save_approval_chain_updates_existing_setting(): void
    {
        $manager = Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Manager', 'slug' => 'manager', 'is_system' => true]);
        $director = Role::firstOrCreate(['slug' => 'director'], ['name' => 'Director', 'slug' => 'director', 'is_system' => true]);

        TimekeepingApprovalSetting::create([
            'company_id' => $this->company->id,
            'type' => 'leave',
            'steps' => [['order' => 1, 'role_id' => $manager->id]],
        ]);

        $this->actingAs($this->admin)
            ->post('/app-settings/timekeeping/approval-chain', [
                'type' => 'leave',
                'steps' => [
                    ['order' => 1, 'role_id' => $manager->id],
                    ['order' => 2, 'role_id' => $director->id],
                ],
            ]);

        $this->assertCount(1, TimekeepingApprovalSetting::where('company_id', $this->company->id)->where('type', 'leave')->get());

        $setting = TimekeepingApprovalSetting::where('company_id', $this->company->id)
            ->where('type', 'leave')
            ->first();

        $this->assertCount(2, $setting->steps);
    }

    public function test_approval_chain_rejects_more_than_three_steps(): void
    {
        $role = Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Manager', 'slug' => 'manager', 'is_system' => true]);

        $this->actingAs($this->admin)
            ->post('/app-settings/timekeeping/approval-chain', [
                'type' => 'leave',
                'steps' => [
                    ['order' => 1, 'role_id' => $role->id],
                    ['order' => 2, 'role_id' => $role->id],
                    ['order' => 3, 'role_id' => $role->id],
                    ['order' => 4, 'role_id' => $role->id],
                ],
            ])
            ->assertSessionHasErrors('steps');
    }

    public function test_approval_chain_rejects_invalid_role(): void
    {
        $this->actingAs($this->admin)
            ->post('/app-settings/timekeeping/approval-chain', [
                'type' => 'leave',
                'steps' => [
                    ['order' => 1, 'role_id' => 99999],
                ],
            ])
            ->assertSessionHasErrors('steps.0.role_id');
    }
}
