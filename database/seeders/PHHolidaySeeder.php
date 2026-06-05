<?php

namespace Database\Seeders;

use App\Modules\Payroll\Models\Holiday;
use Illuminate\Database\Seeder;

class PHHolidaySeeder extends Seeder
{
    public function run(): void
    {
        // Philippine National Holidays (is_recurring = true means they repeat each year by month/day)
        $regularHolidays = [
            ['name' => "New Year's Day", 'date' => '2025-01-01'],
            ['name' => 'Araw ng Kagitingan (Day of Valor)', 'date' => '2025-04-09'],
            ['name' => 'Labor Day', 'date' => '2025-05-01'],
            ['name' => 'Independence Day', 'date' => '2025-06-12'],
            ['name' => 'National Heroes Day', 'date' => '2025-08-25'], // last Monday of August - approximated
            ['name' => 'Bonifacio Day', 'date' => '2025-11-30'],
            ['name' => 'Christmas Day', 'date' => '2025-12-25'],
            ['name' => 'Rizal Day', 'date' => '2025-12-30'],
        ];

        $specialHolidays = [
            ['name' => 'Chinese New Year', 'date' => '2025-01-29'],
            ['name' => 'EDSA People Power Revolution Anniversary', 'date' => '2025-02-25'],
            ['name' => 'Maundy Thursday', 'date' => '2025-04-17'],
            ['name' => 'Good Friday', 'date' => '2025-04-18'],
            ['name' => 'Black Saturday', 'date' => '2025-04-19'],
            ['name' => "All Saints' Day", 'date' => '2025-11-01'],
            ['name' => "All Souls' Day", 'date' => '2025-11-02'],
            ['name' => 'Christmas Eve', 'date' => '2025-12-24'],
            ['name' => "Last Day of the Year (New Year's Eve)", 'date' => '2025-12-31'],
            // 2026
            ['name' => "New Year's Day", 'date' => '2026-01-01'],
            ['name' => 'Chinese New Year', 'date' => '2026-02-17'],
            ['name' => 'EDSA People Power Revolution Anniversary', 'date' => '2026-02-25'],
            ['name' => 'Araw ng Kagitingan (Day of Valor)', 'date' => '2026-04-09'],
            ['name' => 'Maundy Thursday', 'date' => '2026-04-02'],
            ['name' => 'Good Friday', 'date' => '2026-04-03'],
            ['name' => 'Black Saturday', 'date' => '2026-04-04'],
            ['name' => 'Labor Day', 'date' => '2026-05-01'],
            ['name' => 'Independence Day', 'date' => '2026-06-12'],
            ['name' => "All Saints' Day", 'date' => '2026-11-01'],
            ['name' => "All Souls' Day", 'date' => '2026-11-02'],
            ['name' => 'Bonifacio Day', 'date' => '2026-11-30'],
            ['name' => 'Christmas Eve', 'date' => '2026-12-24'],
            ['name' => 'Christmas Day', 'date' => '2026-12-25'],
            ['name' => 'Rizal Day', 'date' => '2026-12-30'],
            ['name' => "Last Day of the Year (New Year's Eve)", 'date' => '2026-12-31'],
        ];

        foreach ($regularHolidays as $holiday) {
            Holiday::firstOrCreate(
                ['name' => $holiday['name'], 'date' => $holiday['date']],
                [
                    'company_id' => null,
                    'type' => 'regular',
                    'is_recurring' => true,
                    'is_active' => true,
                ]
            );
        }

        foreach ($specialHolidays as $holiday) {
            Holiday::firstOrCreate(
                ['name' => $holiday['name'], 'date' => $holiday['date']],
                [
                    'company_id' => null,
                    'type' => 'special',
                    'is_recurring' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}
