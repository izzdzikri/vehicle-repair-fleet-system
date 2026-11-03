<?php

namespace Database\Factories;

use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\SparePart;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobCardPartFactory extends Factory
{
    protected $model = JobCardPart::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'spare_part_id' => SparePart::factory(),
            'quantity' => 1,
            'unit_price' => 50.00,
        ];
    }
}
