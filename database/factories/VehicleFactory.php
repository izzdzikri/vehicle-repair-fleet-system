<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plate_number' => strtoupper(fake()->bothify('??? ####')),
            'brand' => fake()->randomElement(['Toyota', 'Honda', 'Proton', 'Perodua']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2015, 2024),
            'mileage' => fake()->numberBetween(10000, 150000),
            'color' => fake()->safeColorName(),
            'fuel_type' => 'Petrol',
        ];
    }
}
