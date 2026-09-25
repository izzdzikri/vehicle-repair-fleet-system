<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\TripLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TripLogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_individual_user_can_view_own_trip_logs_page(): void
    {
        $ain = User::where('email', 'ain@gmail.com')->first();
        $response = $this->actingAs($ain)->get('/customer/trip-logs');

        $response->assertStatus(200);
        $response->assertSee('Trip Logs & Mileage Tracking');
        $response->assertSee('VKK 3456');
    }

    public function test_individual_user_can_store_trip_and_advance_mileage(): void
    {
        $ain = User::where('email', 'ain@gmail.com')->first();
        $vehicle = Vehicle::where('user_id', $ain->id)->first();
        $initialMileage = $vehicle->mileage;

        $response = $this->actingAs($ain)->post('/customer/trip-logs', [
            'vehicle_id'     => $vehicle->id,
            'trip_date'      => now()->toDateString(),
            'distance_km'    => 55.5,
            'terrain_type'   => 'urban',
            'notes'          => 'Office round trip',
            'update_mileage' => 1,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('trip_logs', [
            'vehicle_id'   => $vehicle->id,
            'distance_km'  => 55.5,
            'terrain_type' => 'urban',
            'notes'        => 'Office round trip',
        ]);

        $this->assertEquals($initialMileage + 56, $vehicle->fresh()->mileage);
    }

    public function test_individual_user_cannot_log_trip_for_another_users_vehicle(): void
    {
        $ain = User::where('email', 'ain@gmail.com')->first();
        $otherVehicle = Vehicle::where('plate_number', 'JQP 1122')->first(); // Owned by Mulia Logistik

        $response = $this->actingAs($ain)->post('/customer/trip-logs', [
            'vehicle_id'   => $otherVehicle->id,
            'trip_date'    => now()->toDateString(),
            'distance_km'  => 30.0,
            'terrain_type' => 'urban',
        ]);

        $response->assertStatus(403);
    }

    public function test_corporate_primary_pic_can_log_trip_for_company_fleet(): void
    {
        $hafiz = User::where('email', 'hafiz@mulialogistik.com.my')->first();
        $fifiVehicle = Vehicle::where('plate_number', 'JDE 7890')->first(); // Fifi is secondary PIC in same company

        $response = $this->actingAs($hafiz)->post('/client/trip-logs', [
            'vehicle_id'   => $fifiVehicle->id,
            'trip_date'    => now()->toDateString(),
            'distance_km'  => 80.0,
            'terrain_type' => 'highway',
            'notes'        => 'JB to Kulai branch transfer',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('trip_logs', [
            'vehicle_id'   => $fifiVehicle->id,
            'distance_km'  => 80.0,
            'terrain_type' => 'highway',
        ]);
    }

    public function test_corporate_secondary_pic_cannot_log_or_delete_trips(): void
    {
        $fifi = User::where('email', 'fifi@mulialogistik.com.my')->first(); // Secondary PIC (Viewer)
        $vehicle = Vehicle::where('plate_number', 'JDE 7890')->first();

        // Attempt to store
        $storeResponse = $this->actingAs($fifi)->post('/client/trip-logs', [
            'vehicle_id'   => $vehicle->id,
            'trip_date'    => now()->toDateString(),
            'distance_km'  => 50.0,
            'terrain_type' => 'urban',
        ]);
        $storeResponse->assertSessionHas('error');

        // Attempt to delete existing trip
        $trip = TripLog::where('vehicle_id', 1)->first();
        $deleteResponse = $this->actingAs($fifi)->delete("/client/trip-logs/{$trip->id}");
        $deleteResponse->assertSessionHas('error');
    }

    public function test_admin_can_view_all_trip_logs_and_delete_entry(): void
    {
        $admin = User::where('email', 'admin@teraju.my')->first();
        $response = $this->actingAs($admin)->get('/admin/trip-logs');
        $response->assertStatus(200);

        $trip = TripLog::first();
        $deleteResponse = $this->actingAs($admin)->delete("/admin/trip-logs/{$trip->id}");
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('trip_logs', ['id' => $trip->id]);
    }
}
