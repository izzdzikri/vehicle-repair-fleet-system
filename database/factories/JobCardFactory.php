<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\JobCard;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobCardFactory extends Factory
{
    protected $model = JobCard::class;

    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'vehicle_id' => Vehicle::factory(),
            'staff_id' => User::factory(),
            'current_stage' => 'received',
            'diagnosis' => 'Initial workshop inspection',
            'total_cost' => 150.00,
        ];
    }
}
