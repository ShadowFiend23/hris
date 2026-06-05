<?php

namespace Database\Factories;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Core\Models\Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Human Resources',
            'Finance',
            'Engineering',
            'Marketing',
            'Sales',
            'Operations',
            'IT',
            'Customer Support',
            'Research & Development',
            'Legal',
        ]);

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
