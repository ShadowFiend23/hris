<?php

namespace Tests\Feature\Timekeeping;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class OvertimeRequestTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id, ['timekeeping']);
    }

    private function createUserWithEmployee(): array
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
        ]);

        return [$user, $employee];
    }

    public function test_overtime_request_stores_window_and_computes_hours(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();

        // Shift 8–5; clocked out at 7:01 PM, requests OT 5:00 PM → 7:01 PM (2.02h).
        $this->actingAs($user)
            ->postJson('/api/timekeeping/overtime/request', [
                'start_date' => '2026-05-20',
                'start_time' => '17:00',
                'end_date' => '2026-05-20',
                'end_time' => '19:01',
                'reason' => 'Project deadline',
            ])
            ->assertCreated()
            ->assertJsonPath('data.hours', '2.02');

        $record = OvertimeRecord::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-05-20 17:00:00', $record->start_at->toDateTimeString());
        $this->assertSame('2026-05-20 19:01:00', $record->end_at->toDateTimeString());
        $this->assertEqualsWithDelta(2.02, (float) $record->hours, 0.001);
        $this->assertSame('pending', $record->status);
    }

    public function test_overtime_request_supports_overnight_window(): void
    {
        [$user, $employee] = $this->createUserWithEmployee();

        // Returned to office 10:00 PM, out 1:30 AM the next day → 3.5h.
        $this->actingAs($user)
            ->postJson('/api/timekeeping/overtime/request', [
                'start_date' => '2026-05-20',
                'start_time' => '22:00',
                'end_date' => '2026-05-21',
                'end_time' => '01:30',
            ])
            ->assertCreated()
            ->assertJsonPath('data.hours', '3.50');

        $record = OvertimeRecord::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-05-20', $record->date->toDateString());
        $this->assertEqualsWithDelta(3.5, (float) $record->hours, 0.001);
    }

    public function test_overtime_request_rejects_time_out_before_time_in(): void
    {
        [$user] = $this->createUserWithEmployee();

        $this->actingAs($user)
            ->postJson('/api/timekeeping/overtime/request', [
                'start_date' => '2026-05-20',
                'start_time' => '19:00',
                'end_date' => '2026-05-20',
                'end_time' => '17:00',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_time');
    }

    public function test_overtime_request_requires_all_window_fields(): void
    {
        [$user] = $this->createUserWithEmployee();

        $this->actingAs($user)
            ->postJson('/api/timekeeping/overtime/request', [
                'start_date' => '2026-05-20',
                'start_time' => '17:00',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date', 'end_time']);
    }
}
