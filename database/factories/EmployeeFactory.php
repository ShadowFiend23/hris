<?php

namespace Database\Factories;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Core\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'employee_id' => $this->faker->unique()->numerify('EMP-####'),
            'first_name' => $this->faker->firstName(),
            'middle_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'date_of_birth' => $this->faker->dateTimeBetween('-65 years', '-18 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'province' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'date_hired' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'date_resigned' => null,
            'employment_status' => 'active',
            'employment_type' => $this->faker->randomElement(['full-time', 'part-time', 'contract']),
            'salary' => $this->faker->numberBetween(20000, 100000),
            'bank_account' => $this->faker->bankAccountNumber(),
            'tin' => $this->faker->numerify('###-###-###-###'),
            'sss_number' => $this->faker->numerify('##-#######-#'),
            'philhealth_number' => $this->faker->numerify('##-##########-#'),
            'pagibig_number' => $this->faker->numerify('####-####-####'),
            'is_active' => true,
        ];
    }
}
