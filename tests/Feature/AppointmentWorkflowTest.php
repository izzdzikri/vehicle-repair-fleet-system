<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\JobCard;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AppointmentWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_view_appointment_details_and_filter_tabs()
    {
        $ain = User::where('email', 'ain@gmail.com')->first();
        $this->actingAs($ain);

        // Individual customer visits appointments index
        $response = $this->get('/customer/appointments');
        $response->assertStatus(200);
        $response->assertSee('All');
        $response->assertSee('Pending');
        $response->assertSee('Confirmed');
        $response->assertSee('Details');

        // Customer has appointment #57 in seed data
        $apt = Appointment::where('user_id', $ain->id)->first();
        if ($apt) {
            $showRes = $this->get("/customer/appointments/{$apt->id}");
            $showRes->assertStatus(200);
            $showRes->assertSee("Appointment #{$apt->id}");
            $showRes->assertSee($apt->service_type);
        }
    }

    public function test_corporate_client_can_view_appointment_details()
    {
        $hafiz = User::where('email', 'hafiz@mulialogistik.com.my')->first();
        $this->actingAs($hafiz);

        $response = $this->get('/client/appointments');
        $response->assertStatus(200);
        $response->assertSee('All');
        $response->assertSee('Confirmed');
        $response->assertSee('Details');

        $apt = Appointment::where('user_id', $hafiz->id)->first();
        if ($apt) {
            $showRes = $this->get("/client/appointments/{$apt->id}");
            $showRes->assertStatus(200);
            $showRes->assertSee("Appointment #{$apt->id}");
        }
    }

    public function test_queue_routes_job_cards_to_role_specific_urls()
    {
        // Create an appointment for today with a linked job card
        $vehicle = Vehicle::first();
        $staff = User::where('role', 'staff')->first();

        $apt = Appointment::create([
            'vehicle_id'   => $vehicle->id,
            'date'         => today()->toDateString(),
            'time'         => '10:00:00',
            'service_type' => 'Brake Inspection',
            'status'       => 'confirmed',
            'is_walkin'    => true,
            'walkin_name'  => 'Test Customer',
        ]);

        $jobCard = JobCard::create([
            'appointment_id' => $apt->id,
            'vehicle_id'     => $vehicle->id,
            'staff_id'       => $staff->id,
            'current_stage'  => 'repairing',
        ]);

        // When staff visits queue, the link points to /staff/job-cards/{id}
        $this->actingAs($staff)
            ->get('/staff/appointments/queue')
            ->assertStatus(200)
            ->assertSee("/staff/job-cards/{$jobCard->id}")
            ->assertDontSee("/admin/job-cards/{$jobCard->id}");

        // When coordinator visits queue, link points to /coordinator/job-cards/{id}
        $coordinator = User::where('role', 'coordinator')->first();
        if ($coordinator) {
            $this->actingAs($coordinator)
                ->get('/coordinator/appointments/queue')
                ->assertStatus(200)
                ->assertSee("/coordinator/job-cards/{$jobCard->id}")
                ->assertDontSee("/admin/job-cards/{$jobCard->id}");
        }
    }

    public function test_coordinator_can_confirm_pending_appointment()
    {
        $coordinator = User::where('role', 'coordinator')->first();
        if (!$coordinator) {
            $this->markTestSkipped('Coordinator account not found.');
        }

        $vehicle = Vehicle::first();
        $apt = Appointment::create([
            'vehicle_id'   => $vehicle->id,
            'date'         => today()->addDays(2)->toDateString(),
            'time'         => '11:00:00',
            'service_type' => 'Oil & Filter Change',
            'status'       => 'pending',
            'is_walkin'    => true,
            'walkin_name'  => 'Walkin Test',
        ]);

        $this->actingAs($coordinator)
            ->patch("/coordinator/appointments/{$apt->id}/confirm")
            ->assertRedirect();

        $apt->refresh();
        $this->assertEquals('confirmed', $apt->status);
    }

    public function test_job_card_create_prepopulates_diagnosis_from_appointment_notes()
    {
        $admin = User::where('role', 'admin')->first();
        $vehicle = Vehicle::first();

        $notes = "=== TERAJU WORKSHOP INTAKE & DIAGNOSTIC BRIEF ===\n• Customer Complaint: Engine ticking sound";
        $apt = Appointment::create([
            'vehicle_id'   => $vehicle->id,
            'date'         => today()->addDay()->toDateString(),
            'time'         => '14:00:00',
            'service_type' => 'Engine Diagnostics',
            'notes'        => $notes,
            'status'       => 'confirmed',
            'is_walkin'    => true,
            'walkin_name'  => 'Encik Razak',
        ]);

        $this->actingAs($admin)
            ->get("/admin/job-cards/create?appointment_id={$apt->id}")
            ->assertStatus(200)
            ->assertSee('Engine ticking sound')
            ->assertSee('TERAJU WORKSHOP INTAKE');
    }

    public function test_coordinator_sees_action_dropdown_and_can_access_inventory_po_link()
    {
        $coordinator = User::where('role', 'coordinator')->first();
        if ($coordinator) {
            $this->actingAs($coordinator)
                ->get('/coordinator/appointments')
                ->assertStatus(200)
                ->assertSee('Details');
        }

        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $this->actingAs($admin)
                ->get('/admin/spare-parts')
                ->assertStatus(200)
                ->assertSee('Purchase Orders');
        }
    }

    public function test_admin_queue_displays_dispatch_job_for_unstarted_appointments()
    {
        $admin = User::where('role', 'admin')->first();
        $vehicle = Vehicle::first();

        $apt = Appointment::create([
            'vehicle_id'   => $vehicle->id,
            'date'         => today()->toDateString(),
            'time'         => '16:00:00',
            'service_type' => 'Battery Replacement',
            'status'       => 'confirmed',
            'is_walkin'    => true,
            'walkin_name'  => 'Late Walkin',
        ]);

        $this->actingAs($admin)
            ->get('/admin/appointments/queue')
            ->assertStatus(200)
            ->assertSee('Dispatch Job')
            ->assertSee("/admin/job-cards/create?appointment_id={$apt->id}");
    }
}
