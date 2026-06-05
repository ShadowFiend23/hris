<?php

namespace Database\Factories;

use App\Modules\Core\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Core\Models\Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('MOD_????'),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'icon' => 'icon-module',
            'order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    /**
     * Timekeeping module state.
     */
    public function timekeeping(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'TIMEKEEPING',
            'name' => 'Timekeeping',
            'description' => 'Attendance, leave, shifts, and overtime management',
        ]);
    }
}
