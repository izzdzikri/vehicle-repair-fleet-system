<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SecurityPolicyAndWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_junior_staff_without_invoice_permission_cannot_mark_paid_via_chatbot(): void
    {
        $mechanic = User::factory()->create([
            'role' => 'staff',
            'staff_role' => 'mechanic',
            'permissions' => ['job_cards.manage_all'], // lacks invoice.manage
        ]);

        $invoice = Invoice::first();
        if (! $invoice) {
            $jobCard = JobCard::first() ?? JobCard::factory()->create();
            $invoice = Invoice::create([
                'job_card_id' => $jobCard->id,
                'invoice_number' => 'INV-TEST-001',
                'customer_name' => 'Test Customer',
                'total' => 100.00,
                'amount_paid' => 0.00,
                'status' => 'unpaid',
            ]);
        }

        // Session sets pending payment
        $response = $this->actingAs($mechanic)
            ->withSession(['pending_invoice_payment' => [
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total,
            ]])
            ->postJson('/chatbot/reply', [
                'message' => 'sahkan bayaran',
            ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('kebenaran', strtolower($response->json('reply')));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => $invoice->status,
        ]);
    }

    public function test_job_card_stage_transition_cannot_skip_from_received_to_completed(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        $jobCard = JobCard::factory()->create([
            'current_stage' => 'received',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/admin/job-cards/{$jobCard->id}/stage", [
                'current_stage' => 'completed',
            ]);

        $response->assertStatus(422);
        $this->assertEquals('received', $jobCard->fresh()->current_stage);
    }

    public function test_job_card_stage_valid_progression(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        $jobCard = JobCard::factory()->create([
            'current_stage' => 'received',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/admin/job-cards/{$jobCard->id}/stage", [
                'current_stage' => 'diagnosing',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('diagnosing', $jobCard->fresh()->current_stage);
    }

    public function test_corporate_user_cannot_access_vehicles_of_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create([
            'role' => 'corporate',
            'company_id' => $companyA->id,
            'pic_role' => 'primary',
        ]);

        $userB = User::factory()->create([
            'role' => 'corporate',
            'company_id' => $companyB->id,
            'pic_role' => 'primary',
        ]);

        $vehicleB = Vehicle::factory()->create([
            'user_id' => $userB->id,
        ]);

        $response = $this->actingAs($userA)
            ->get("/client/vehicles/{$vehicleB->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_chatbot_booking_does_not_overwrite_existing_vehicle_specs(): void
    {
        $owner = User::factory()->create(['role' => 'individual']);
        $realVehicle = Vehicle::factory()->create([
            'user_id' => $owner->id,
            'plate_number' => 'SAFE 9999',
            'brand' => 'Honda',
            'model' => 'Civic',
            'year' => 2020,
            'mileage' => 45000,
        ]);

        $bookingDraft = [
            'plate_number' => 'SAFE 9999',
            'car_brand' => 'MaliciousBrand',
            'car_model' => 'HackedModel',
            'car_year' => 1995,
            'mileage' => '999999 km',
            'customer_name' => 'Attacker',
            'phone' => '0123456789',
            'service_type' => 'Oil & Filter Change',
            'diagnostic_notes' => 'Diagnostic test',
            'date' => now()->addDays(5)->toDateString(),
            'date_label' => now()->addDays(5)->format('d M Y'),
            'time' => '10:00:00',
            'time_label' => '10:00 AM',
        ];

        $response = $this->withSession(['pending_chatbot_booking' => $bookingDraft])
            ->postJson('/chatbot/reply', [
                'action' => 'confirm_booking',
                'message' => 'ok',
            ]);

        $response->assertStatus(200);

        // Verify the original vehicle specifications remain untouched
        $realVehicle->refresh();
        $this->assertEquals('Honda', $realVehicle->brand);
        $this->assertEquals('Civic', $realVehicle->model);
        $this->assertEquals(2020, $realVehicle->year);
        $this->assertEquals(45000, $realVehicle->mileage);
    }
}
