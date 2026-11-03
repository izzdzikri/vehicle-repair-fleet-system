<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleBasedAccessControlAndIdorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_individual_user_cannot_view_or_mutate_another_users_vehicle(): void
    {
        $userA = User::factory()->create(['role' => 'individual']);
        $userB = User::factory()->create(['role' => 'individual']);

        $vehicleB = Vehicle::factory()->create(['user_id' => $userB->id]);

        // Attempt show
        $this->actingAs($userA)
            ->get("/customer/vehicles/{$vehicleB->id}")
            ->assertStatus(403);

        // Attempt edit
        $this->actingAs($userA)
            ->get("/customer/vehicles/{$vehicleB->id}/edit")
            ->assertStatus(403);

        // Attempt update
        $this->actingAs($userA)
            ->put("/customer/vehicles/{$vehicleB->id}", [
                'brand' => 'HackedBrand',
                'model' => 'HackedModel',
                'year' => 2022,
            ])
            ->assertStatus(403);

        // Attempt delete
        $this->actingAs($userA)
            ->delete("/customer/vehicles/{$vehicleB->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('vehicles', ['id' => $vehicleB->id]);
    }

    public function test_individual_user_cannot_view_or_download_another_users_invoice(): void
    {
        $userA = User::factory()->create(['role' => 'individual']);
        $userB = User::factory()->create(['role' => 'individual']);

        $vehicleB = Vehicle::factory()->create(['user_id' => $userB->id]);
        $jobCardB = JobCard::factory()->create(['vehicle_id' => $vehicleB->id, 'current_stage' => 'completed']);
        $invoiceB = Invoice::create([
            'job_card_id' => $jobCardB->id,
            'invoice_number' => 'INV-TEST-'.rand(1000, 9999),
            'customer_name' => $userB->name,
            'total' => 250.00,
            'amount_paid' => 0.00,
            'status' => 'unpaid',
        ]);

        $this->actingAs($userA)
            ->get("/customer/invoices/{$invoiceB->id}")
            ->assertStatus(403);

        $this->actingAs($userA)
            ->get("/customer/invoices/{$invoiceB->id}/pdf")
            ->assertStatus(403);
    }

    public function test_individual_user_cannot_view_another_users_appointment(): void
    {
        $userA = User::factory()->create(['role' => 'individual']);
        $userB = User::factory()->create(['role' => 'individual']);

        $vehicleB = Vehicle::factory()->create(['user_id' => $userB->id]);
        $appointmentB = Appointment::factory()->create([
            'user_id' => $userB->id,
            'vehicle_id' => $vehicleB->id,
            'status' => 'confirmed',
        ]);

        $this->actingAs($userA)
            ->get("/customer/appointments/{$appointmentB->id}")
            ->assertStatus(403);
    }

    public function test_corporate_pic_cannot_view_or_mutate_vehicles_of_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $picA = User::factory()->create([
            'role' => 'corporate',
            'company_id' => $companyA->id,
            'pic_role' => 'primary',
        ]);
        $picB = User::factory()->create([
            'role' => 'corporate',
            'company_id' => $companyB->id,
            'pic_role' => 'primary',
        ]);

        $vehicleB = Vehicle::factory()->create(['user_id' => $picB->id]);

        $this->actingAs($picA)
            ->get("/client/vehicles/{$vehicleB->id}")
            ->assertStatus(403);

        $this->actingAs($picA)
            ->put("/client/vehicles/{$vehicleB->id}", [
                'brand' => 'Tampered',
                'model' => 'Vehicle',
                'year' => 2023,
            ])
            ->assertStatus(403);

        $this->actingAs($picA)
            ->delete("/client/vehicles/{$vehicleB->id}")
            ->assertStatus(403);
    }

    public function test_corporate_secondary_pic_cannot_mutate_fleet_records(): void
    {
        $company = Company::factory()->create();
        $secondaryPic = User::factory()->create([
            'role' => 'corporate',
            'company_id' => $company->id,
            'pic_role' => 'secondary',
        ]);

        $vehicle = Vehicle::factory()->create(['user_id' => $secondaryPic->id]);

        // Attempt adding vehicle
        $response = $this->actingAs($secondaryPic)->post('/client/vehicles', [
            'plate_number' => 'VEC 1122',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2021,
        ]);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('vehicles', ['plate_number' => 'VEC 1122']);

        // Attempt editing vehicle (forbidden for Viewer PIC)
        $this->actingAs($secondaryPic)
            ->get("/client/vehicles/{$vehicle->id}/edit")
            ->assertStatus(403);

        // Attempt deleting vehicle (forbidden by policy)
        $deleteResponse = $this->actingAs($secondaryPic)->delete("/client/vehicles/{$vehicle->id}");
        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);

        // Attempt logging trip
        $tripResponse = $this->actingAs($secondaryPic)->post('/client/trip-logs', [
            'vehicle_id' => $vehicle->id,
            'trip_date' => now()->toDateString(),
            'distance_km' => 50,
            'terrain_type' => 'highway',
        ]);
        $tripResponse->assertSessionHas('error');
    }

    public function test_corporate_user_without_company_is_blocked_from_fleet_endpoints(): void
    {
        $unassignedCorporate = User::factory()->create([
            'role' => 'corporate',
            'company_id' => null,
            'pic_role' => 'primary',
        ]);

        $this->actingAs($unassignedCorporate)->get('/client/dashboard')->assertStatus(403);
        $this->actingAs($unassignedCorporate)->get('/client/vehicles')->assertStatus(403);
        $this->actingAs($unassignedCorporate)->get('/client/trip-logs')->assertStatus(403);
        $this->actingAs($unassignedCorporate)->get('/client/invoices')->assertStatus(403);
        $this->actingAs($unassignedCorporate)->get('/client/company')->assertStatus(403);
    }

    public function test_staff_cannot_access_admin_management_routes(): void
    {
        $mechanic = User::factory()->create([
            'role' => 'staff',
            'staff_role' => 'mechanic',
        ]);

        $this->actingAs($mechanic)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($mechanic)->get('/admin/users')->assertStatus(403);
        $this->actingAs($mechanic)->get('/admin/companies')->assertStatus(403);
        $this->actingAs($mechanic)->get('/admin/reports')->assertStatus(403);
    }

    public function test_cannot_delete_vehicle_with_active_job_card_or_unpaid_invoice(): void
    {
        $owner = User::factory()->create(['role' => 'individual']);
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->id]);

        $activeJob = JobCard::factory()->create([
            'vehicle_id' => $vehicle->id,
            'current_stage' => 'repairing',
        ]);

        // Attempt delete when active job exists
        $response = $this->actingAs($owner)->delete("/customer/vehicles/{$vehicle->id}");
        $response->assertSessionHas('error', 'Cannot delete vehicle with active job cards in progress.');
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);

        // Complete the job card and create unpaid invoice
        $activeJob->update(['current_stage' => 'completed']);
        $invoice = Invoice::create([
            'job_card_id' => $activeJob->id,
            'invoice_number' => 'INV-TEST-'.rand(1000, 9999),
            'customer_name' => $owner->name,
            'total' => 150.00,
            'amount_paid' => 0.00,
            'status' => 'unpaid',
        ]);

        $response2 = $this->actingAs($owner)->delete("/customer/vehicles/{$vehicle->id}");
        $response2->assertSessionHas('error', 'Cannot delete vehicle with outstanding unpaid invoices.');
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);

        // Mark invoice paid, now deletion succeeds
        $invoice->update(['status' => 'paid', 'amount_paid' => 150.00]);
        $response3 = $this->actingAs($owner)->delete("/customer/vehicles/{$vehicle->id}");
        $response3->assertSessionHas('success');
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }
}
