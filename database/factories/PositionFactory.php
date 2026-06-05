<?php

namespace Database\Factories;

use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Core\Models\Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'position_name' => $this->faker->jobTitle(),
            'is_active' => true,
        ];
    }
}
