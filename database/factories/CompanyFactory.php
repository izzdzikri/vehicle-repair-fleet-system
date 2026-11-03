<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'registration_no' => fake()->numerify('######-?').' (Sdn Bhd)',
            'address' => fake()->address(),
            'phone' => '07-'.fake()->numerify('#######'),
            'email' => fake()->companyEmail(),
            'person_in_charge' => fake()->name(),
        ];
    }
}
