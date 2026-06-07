<?php

namespace Tests\Feature\Payroll;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Payroll\Models\PayrollSetting;
use App\Modules\Payroll\Services\PayrollPeriodService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class PayrollPeriodTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    private User $user;

    private PayrollSetting $setting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id, ['payroll']);

        $role = Role::create(['name' => 'Payroll Admin', 'slug' => 'payroll-admin-'.uniqid()]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'payroll.run'],
            ['name' => 'Run Payroll', 'slug' => 'payroll.run', 'group' => 'payroll']
        );
        $role->permissions()->attach($permission->id);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->roles()->attach($role->id);

        Employee::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->user->load('roles.permissions');

        $this->setting = PayrollSetting::create([
            'company_id' => $this->company->id,
            'period_type' => 'semi_monthly',
            'pay_day_1' => 15,
            'pay_day_2' => 30,
            'work_days_per_month' => 26,
            'cutoff_offset_days' => 15,
            'is_active' => true,
        ]);
    }

    private function createPeriod(string $start, string $end, string $status = 'finalized'): PayrollPeriod
    {
        return PayrollPeriod::create([
            'company_id' => $this->company->id,
            'payroll_setting_id' => $this->setting->id,
            'start_date' => $start,
            'end_date' => $end,
            'pay_date' => $end,
            'status' => $status,
        ]);
    }

    // ───────── Auto-generation ─────────

    public function test_first_period_is_auto_generated_from_today(): void
    {
        // No existing periods — system uses today as reference
        $this->actingAs($this->user)
            ->post('/payroll/periods', ['pay_date' => '2026-06-15'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payroll_periods', 1);
        $period = PayrollPeriod::first();

        // start_date and end_date are auto-generated (not from user input)
        $this->assertNotNull($period->start_date);
        $this->assertNotNull($period->end_date);
        $this->assertEquals('2026-06-15', $period->pay_date->toDateString());
    }

    public function test_next_period_starts_day_after_last_period_ends(): void
    {
        $this->createPeriod('2026-05-01', '2026-05-15', 'finalized');

        $this->actingAs($this->user)
            ->post('/payroll/periods', ['pay_date' => '2026-05-30'])
            ->assertSessionHasNoErrors();

        $new = PayrollPeriod::orderBy('start_date', 'desc')->first();
        $this->assertEquals('2026-05-16', $new->start_date->toDateString());
        $this->assertEquals('2026-05-31', $new->end_date->toDateString());
    }

    public function test_next_period_wraps_to_next_month_correctly(): void
    {
        $this->createPeriod('2026-05-16', '2026-05-31', 'finalized');

        $this->actingAs($this->user)
            ->post('/payroll/periods', ['pay_date' => '2026-06-15'])
            ->assertSessionHasNoErrors();

        $new = PayrollPeriod::orderBy('start_date', 'desc')->first();
        $this->assertEquals('2026-06-01', $new->start_date->toDateString());
        $this->assertEquals('2026-06-15', $new->end_date->toDateString());
    }

    public function test_cancelled_periods_are_skipped_when_computing_next(): void
    {
        $this->createPeriod('2026-05-01', '2026-05-15', 'finalized');
        $this->createPeriod('2026-05-16', '2026-05-31', 'cancelled'); // skipped

        $this->actingAs($this->user)
            ->post('/payroll/periods', ['pay_date' => '2026-06-15'])
            ->assertSessionHasNoErrors();

        $new = PayrollPeriod::where('status', 'draft')->first();
        // Next after the finalized May 1–15 (ignoring cancelled May 16–31)
        $this->assertEquals('2026-05-16', $new->start_date->toDateString());
    }

    public function test_cannot_create_period_when_slot_is_already_occupied(): void
    {
        // Simulate a race condition: two concurrent requests both computed June 1–15
        // as the next slot. The first succeeded; we mock the service so the second
        // request also returns the same dates, which should then be rejected.
        $this->createPeriod('2026-06-01', '2026-06-15', 'draft');

        $this->mock(PayrollPeriodService::class, function ($mock): void {
            $mock->shouldReceive('generateNextPeriod')->andReturn([
                'start_date' => Carbon::parse('2026-06-01'),
                'end_date' => Carbon::parse('2026-06-15'),
                'pay_date' => Carbon::parse('2026-06-15'),
            ]);
        });

        $this->actingAs($this->user)
            ->post('/payroll/periods', ['pay_date' => '2026-06-15'])
            ->assertSessionHasErrors('period');
    }

    public function test_pay_date_is_required(): void
    {
        $this->actingAs($this->user)
            ->post('/payroll/periods', [])
            ->assertSessionHasErrors('pay_date');
    }

    public function test_user_without_payroll_run_cannot_create_period(): void
    {
        $other = User::factory()->create(['company_id' => $this->company->id]);
        Employee::factory()->create(['user_id' => $other->id, 'company_id' => $this->company->id]);

        $this->actingAs($other)
            ->post('/payroll/periods', ['pay_date' => '2026-06-15'])
            ->assertStatus(403);
    }

    // ───────── Cancel ─────────

    public function test_can_cancel_a_draft_period(): void
    {
        $period = $this->createPeriod('2026-05-01', '2026-05-15', 'draft');

        $this->actingAs($this->user)
            ->post("/payroll/periods/{$period->id}/cancel")
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_periods', ['id' => $period->id, 'status' => 'cancelled']);
    }

    public function test_cannot_cancel_a_finalized_period(): void
    {
        $period = $this->createPeriod('2026-05-01', '2026-05-15', 'finalized');

        $this->actingAs($this->user)
            ->post("/payroll/periods/{$period->id}/cancel")
            ->assertStatus(403);
    }

    public function test_cannot_cancel_a_processing_period(): void
    {
        $period = $this->createPeriod('2026-05-01', '2026-05-15', 'processing');

        $this->actingAs($this->user)
            ->post("/payroll/periods/{$period->id}/cancel")
            ->assertStatus(403);
    }

    // ───────── Delete ─────────

    public function test_can_delete_a_draft_period(): void
    {
        $period = $this->createPeriod('2026-05-01', '2026-05-15', 'draft');

        $this->actingAs($this->user)
            ->delete("/payroll/periods/{$period->id}")
            ->assertRedirect('/payroll/periods');

        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    public function test_can_delete_a_cancelled_period(): void
    {
        $period = $this->createPeriod('2026-05-01', '2026-05-15', 'cancelled');

        $this->actingAs($this->user)
            ->delete("/payroll/periods/{$period->id}")
            ->assertRedirect('/payroll/periods');

        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    public function test_cannot_delete_a_finalized_period(): void
    {
        $period = $this->createPeriod('2026-05-01', '2026-05-15', 'finalized');

        $this->actingAs($this->user)
            ->delete("/payroll/periods/{$period->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('payroll_periods', ['id' => $period->id]);
    }

    public function test_cannot_delete_a_processing_period(): void
    {
        $period = $this->createPeriod('2026-05-01', '2026-05-15', 'processing');

        $this->actingAs($this->user)
            ->delete("/payroll/periods/{$period->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('payroll_periods', ['id' => $period->id]);
    }
}
