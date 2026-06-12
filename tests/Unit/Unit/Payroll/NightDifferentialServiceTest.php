<?php

namespace Tests\Unit\Unit\Payroll;

use App\Modules\Payroll\Services\NightDifferentialService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class NightDifferentialServiceTest extends TestCase
{
    private NightDifferentialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NightDifferentialService;
    }

    public function test_no_nd_for_daytime_shift(): void
    {
        $clockIn = Carbon::parse('2026-05-13 08:00:00');
        $clockOut = Carbon::parse('2026-05-13 17:00:00');

        $ndHours = $this->service->computeNightHours($clockIn, $clockOut);
        $this->assertSame(0.0, $ndHours);
    }

    public function test_full_night_shift_8_hours(): void
    {
        // 10 PM to 6 AM = 8 hours ND
        $clockIn = Carbon::parse('2026-05-13 22:00:00');
        $clockOut = Carbon::parse('2026-05-14 06:00:00');

        $ndHours = $this->service->computeNightHours($clockIn, $clockOut);
        $this->assertEqualsWithDelta(8.0, $ndHours, 0.01);
    }

    public function test_partial_night_shift(): void
    {
        // 8 PM to 11 PM → only 10PM–11PM = 1 hour ND
        $clockIn = Carbon::parse('2026-05-13 20:00:00');
        $clockOut = Carbon::parse('2026-05-13 23:00:00');

        $ndHours = $this->service->computeNightHours($clockIn, $clockOut);
        $this->assertEqualsWithDelta(1.0, $ndHours, 0.01);
    }

    public function test_nd_pay_computed_correctly(): void
    {
        // 2 hours ND at ₱100/hr → 10% × 2hrs × ₱100 = ₱20
        $clockIn = Carbon::parse('2026-05-13 22:00:00');
        $clockOut = Carbon::parse('2026-05-14 00:00:00');

        $ndPay = $this->service->compute($clockIn, $clockOut, 100.0);
        $this->assertEqualsWithDelta(20.0, $ndPay, 0.01);
    }

    public function test_break_is_excluded_and_capped_at_worked_hours(): void
    {
        // 22:00–06:00 is 8h in the band, but a 60-min break leaves only 7h worked.
        $clockIn = Carbon::parse('2026-05-13 22:00:00');
        $clockOut = Carbon::parse('2026-05-14 06:00:00');

        $ndHours = $this->service->computeNightHours($clockIn, $clockOut, 60, 7.0);

        $this->assertSame(7.0, $ndHours);
        $this->assertLessThanOrEqual(7.0, $ndHours);
    }
}
