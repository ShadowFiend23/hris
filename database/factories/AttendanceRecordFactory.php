<?php

namespace Database\Factories;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Timekeeping\Models\AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d');

        return [
            'employee_id' => Employee::factory(),
            'company_id' => Company::factory(),
            'date' => $date,
            'clock_in' => $date.' 08:00:00',
            'morning_out' => null,
            'afternoon_in' => null,
            'clock_out' => $date.' 17:00:00',
            'break_duration' => 60,
            'total_hours' => 8.00,
            'status' => 'present',
            'source' => 'manual',
        ];
    }
}
