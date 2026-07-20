<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $location = Location::factory()->create();
        
        return [
            'location_id' => $location->id,
            'department_id' => Department::factory()->create()->id,
            'designation_id' => Designation::factory()->create()->id,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('##########'),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'aadhaar' => $this->faker->numerify('############'),
            'pan' => $this->faker->regexify('[A-Z]{5}[0-9]{4}[A-Z]{1}'),
            'bank_account' => $this->faker->bankAccountNumber(),
            'bank_name' => $this->faker->company(),
            'ifsc_code' => $this->faker->regexify('[A-Z]{4}0[A-Z0-9]{6}'),
            'date_of_joining' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'status' => 'confirmed',
        ];
    }
}
