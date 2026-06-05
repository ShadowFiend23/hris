<?php

namespace Database\Factories;

use App\Modules\Core\Models\Company;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Timekeeping\Models\ShiftTemplate>
 */
class ShiftTemplateFactory extends Factory
{
    protected $model = ShiftTemplate::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->words(2, true).' Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'duration_hours' => 8.00,
            'break_duration' => 60,
            'break_start_time' => null,
            'break_end_time' => null,
            'work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
        ];
    }

    public function withSplitShift(): static
    {
        return $this->state([
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);
    }
}
