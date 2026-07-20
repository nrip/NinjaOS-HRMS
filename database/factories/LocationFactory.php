<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $states = ['Delhi', 'Haryana', 'Maharashtra', 'Karnataka', 'Uttar Pradesh', 'Gujarat', 'West Bengal', 'Jharkhand', 'Goa'];
        
        return [
            'name' => $this->faker->company(),
            'state' => $this->faker->randomElement($states),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
            'pin_code' => $this->faker->postcode(),
            'is_active' => true,
        ];
    }
}
