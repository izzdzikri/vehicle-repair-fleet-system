<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\JobCard;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\SparePart;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PhaseThreeFeaturesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_today_queue_displays_realtime_bay_utilization_and_capacity()
    {
        $admin = User::where('role', 'admin')->first();
        Setting::set('daily_appointment_capacity', 30);

        $response = $this->actingAs($admin)->get('/admin/appointments/queue');

        $response->assertStatus(200);
        $response->assertSee('Real-Time Workshop Bay Capacity', false);
        $response->assertSee('Daily Booking Load');
        $response->assertSee('In Service Bays');
        $response->assertSee('Completed Today');
        $response->assertSee('Intake Slots Available');
    }

    public function test_auto_replenishment_and_batch_ordering_purchase_orders()
    {
        $admin = User::where('role', 'admin')->first();
        $supplier = Supplier::first() ?? Supplier::create([
            'name'           => 'Test Auto Parts Supply',
            'contact_person' => 'Mr. Lim',
            'phone'          => '012-3456789',
        ]);

        $partA = SparePart::create([
            'name'          => 'Test Air Filter Part A',
            'part_number'   => 'FLT-TEST-001',
            'category'      => 'Filters',
            'stock'         => 2,
            'min_stock'     => 5,
            'max_stock'     => 20,
            'unit_price'    => 45.00,
            'supplier_name' => $supplier->name,
        ]);

        $partB = SparePart::create([
            'name'          => 'Test Spark Plug Part B',
            'part_number'   => 'SPK-TEST-002',
            'category'      => 'Ignition',
            'stock'         => 1,
            'min_stock'     => 10,
            'max_stock'     => 30,
            'unit_price'    => 22.50,
            'supplier_name' => $supplier->name,
        ]);

        // Visiting purchase orders index triggers autoGenerate()
        $this->actingAs($admin)->get('/admin/purchase-orders');

        $poA = PurchaseOrder::where('spare_part_id', $partA->id)->where('status', 'draft')->first();
        $poB = PurchaseOrder::where('spare_part_id', $partB->id)->where('status', 'draft')->first();

        $this->assertNotNull($poA);
        $this->assertNotNull($poB);
        $this->assertEquals($supplier->id, $poA->supplier_id);

        // Perform batch order
        $response = $this->actingAs($admin)->post('/admin/purchase-orders/batch-order', [
            'supplier_id' => $supplier->id,
            'po_ids'      => [$poA->id, $poB->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('ordered', $poA->fresh()->status);
        $this->assertEquals('ordered', $poB->fresh()->status);
        $this->assertNotNull($poA->fresh()->ordered_at);
        $this->assertNotNull($poB->fresh()->ordered_at);
    }

    public function test_corporate_client_can_view_fleet_maintenance_statement()
    {
        $corporate = User::where('role', 'corporate')->first();
        $this->assertNotNull($corporate);

        $response = $this->actingAs($corporate)->get('/client/statement');

        $response->assertStatus(200);
        $response->assertSee('TERAJU SETIA ENTERPRISE');
        $response->assertSee('Fleet Maintenance Statement');
        $response->assertSee('Vehicle Expense Allocation Breakdown');
        $response->assertSee('Itemized Service & Repair Ledger', false);
        $response->assertSee('Print Statement');
    }

    public function test_corporate_client_can_download_fleet_statement_pdf()
    {
        $corporate = User::where('role', 'corporate')->first();
        $this->assertNotNull($corporate);

        $response = $this->actingAs($corporate)->get('/client/statement?format=pdf');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_corporate_dashboard_contains_link_to_fleet_statement()
    {
        $corporate = User::where('role', 'corporate')->first();
        $this->assertNotNull($corporate);

        $response = $this->actingAs($corporate)->get('/client/dashboard');

        $response->assertStatus(200);
        $response->assertSee('/client/statement');
        $response->assertSee('Fleet Statement');
    }
}
