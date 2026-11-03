<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'date' => now()->addDays(2)->toDateString(),
            'time' => '10:00:00',
            'service_type' => 'Oil & Filter Change',
            'status' => 'confirmed',
            'is_walkin' => false,
        ];
    }
}
