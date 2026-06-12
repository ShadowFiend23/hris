<?php

namespace Tests\Feature\Timekeeping;

use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use App\Modules\Timekeeping\Services\DtrService;
use Carbon\Carbon;
use Tests\TestCase;

class DtrServiceTest extends TestCase
{
    private function undertime(string $start, string $end, string $clockIn, string $clockOut, string $date): int
    {
        $shift = new ShiftTemplate(['start_time' => $start, 'end_time' => $end]);
        $record = new AttendanceRecord(['clock_in' => $clockIn, 'clock_out' => $clockOut]);

        return (new DtrService)->undertimeMinutesForRecord($record, $shift, Carbon::parse($date));
    }

    public function test_day_shift_on_time_has_no_undertime(): void
    {
        $this->assertSame(0, $this->undertime('08:00:00', '17:00:00', '2026-06-09 08:00:00', '2026-06-09 17:00:00', '2026-06-09'));
    }

    public function test_night_shift_late_arrival_across_midnight(): void
    {
        // Shift 22:00–06:00; clocked in 22:30 (30 min late), out 06:00 next day (on time).
        $this->assertSame(30, $this->undertime('22:00:00', '06:00:00', '2026-06-09 22:30:00', '2026-06-10 06:00:00', '2026-06-09'));
    }

    public function test_night_shift_early_departure_across_midnight(): void
    {
        // On time in, but left 05:30 next day (30 min before the 06:00 official end).
        $this->assertSame(30, $this->undertime('22:00:00', '06:00:00', '2026-06-09 22:00:00', '2026-06-10 05:30:00', '2026-06-09'));
    }
}
