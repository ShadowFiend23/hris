<?php

namespace Database\Seeders;

use App\Modules\Payroll\Models\Holiday;
use Illuminate\Database\Seeder;

class PHHolidaySeeder extends Seeder
{
    public function run(): void
    {
        // Fixed-date REGULAR holidays — recurring each year by month/day (Labor Code Art. 94).
        // Base dates use 2026 but is_recurring matches them in any year by month+day.
        $regularRecurring = [
            ['name' => "New Year's Day", 'date' => '2026-01-01'],
            ['name' => 'Araw ng Kagitingan (Day of Valor)', 'date' => '2026-04-09'],
            ['name' => 'Labor Day', 'date' => '2026-05-01'],
            ['name' => 'Independence Day', 'date' => '2026-06-12'],
            ['name' => 'Bonifacio Day', 'date' => '2026-11-30'],
            ['name' => 'Christmas Day', 'date' => '2026-12-25'],
            ['name' => 'Rizal Day', 'date' => '2026-12-30'],
        ];

        // Movable REGULAR holidays — proclaimed per year (non-recurring), correct 2026 dates.
        $regularMovable = [
            ['name' => 'Maundy Thursday', 'date' => '2026-04-02'],
            ['name' => 'Good Friday', 'date' => '2026-04-03'],
            ['name' => 'National Heroes Day', 'date' => '2026-08-31'], // last Monday of August
        ];

        // SPECIAL (non-working) holidays — 2026 proclamation, non-recurring.
        $specialHolidays = [
            ['name' => 'Chinese New Year', 'date' => '2026-02-17'],
            ['name' => 'EDSA People Power Revolution Anniversary', 'date' => '2026-02-25'],
            ['name' => 'Black Saturday', 'date' => '2026-04-04'],
            ['name' => 'Ninoy Aquino Day', 'date' => '2026-08-21'],
            ['name' => "All Saints' Day", 'date' => '2026-11-01'],
            ['name' => "All Souls' Day", 'date' => '2026-11-02'],
            ['name' => 'Feast of the Immaculate Conception', 'date' => '2026-12-08'],
            ['name' => 'Christmas Eve', 'date' => '2026-12-24'],
            ['name' => "Last Day of the Year (New Year's Eve)", 'date' => '2026-12-31'],
        ];

        foreach ($regularRecurring as $holiday) {
            $this->upsertHoliday($holiday['name'], $holiday['date'], 'regular', true);
        }

        foreach ($regularMovable as $holiday) {
            $this->upsertHoliday($holiday['name'], $holiday['date'], 'regular', false);
        }

        foreach ($specialHolidays as $holiday) {
            $this->upsertHoliday($holiday['name'], $holiday['date'], 'special', false);
        }
    }

    private function upsertHoliday(string $name, string $date, string $type, bool $recurring): void
    {
        Holiday::updateOrCreate(
            ['name' => $name, 'date' => $date],
            [
                'company_id' => null,
                'type' => $type,
                'is_recurring' => $recurring,
                'is_active' => true,
            ]
        );
    }
}
