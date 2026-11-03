<?php

namespace Database\Factories;

use App\Models\SparePart;
use Illuminate\Database\Eloquent\Factories\Factory;

class SparePartFactory extends Factory
{
    protected $model = SparePart::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Filter',
            'part_number' => strtoupper(fake()->bothify('PART-####-??')),
            'category' => 'Filter',
            'stock' => 20,
            'min_stock' => 5,
            'unit_price' => fake()->randomFloat(2, 25, 300),
        ];
    }
}
