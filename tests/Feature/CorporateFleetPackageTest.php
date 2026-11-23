<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobType;
use App\Models\MaintenanceAlert;
use App\Models\SparePart;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Prediction\InventoryDemandAdvisoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CorporateFleetPackageTest extends TestCase
{
    use DatabaseTransactions;

    protected User $primaryPic;
    protected User $secondaryPic;
    protected User $individualUser;
    protected Company $company;
    protected Vehicle $vehicle1;
    protected Vehicle $vehicle2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name'            => 'Swift Haulage Logistics Sdn Bhd',
            'registration_no' => 'SHL-2024-8899',
            'address'         => 'Plot 12, West Port Industrial Zone, Port Klang, Selangor',
            'contact_email'   => 'fleet@swifthaulage.com.my',
            'contact_phone'   => '+60 3-3168 9900',
        ]);

        $this->primaryPic = User::factory()->create([
            'name'       => 'Captain Ahmad (Primary PIC)',
            'email'      => 'ahmad.primary@swifthaulage.com.my',
            'role'       => 'corporate',
            'pic_role'   => 'primary',
            'company_id' => $this->company->id,
            'status'     => 'active',
        ]);

        $this->secondaryPic = User::factory()->create([
            'name'       => 'Siti Viewer (Secondary PIC)',
            'email'      => 'siti.viewer@swifthaulage.com.my',
            'role'       => 'corporate',
            'pic_role'   => 'secondary',
            'company_id' => $this->company->id,
            'status'     => 'active',
        ]);

        $this->individualUser = User::factory()->create([
            'name'   => 'Ali Personal Driver',
            'email'  => 'ali.personal@example.com',
            'role'   => 'individual',
            'status' => 'active',
        ]);

        $this->vehicle1 = Vehicle::create([
            'user_id'      => $this->primaryPic->id,
            'plate_number' => 'SWIFT-8001',
            'brand'        => 'Isuzu',
            'model'        => 'Giga Prime 4x2',
            'year'         => 2021,
            'mileage'      => 142000,
        ]);

        $this->vehicle2 = Vehicle::create([
            'user_id'      => $this->primaryPic->id,
            'plate_number' => 'SWIFT-8002',
            'brand'        => 'Hino',
            'model'        => '500 Series 6x2',
            'year'         => 2020,
            'mileage'      => 189000,
        ]);
    }

    public function test_corporate_primary_pic_can_view_fleet_health_report(): void
    {
        $jobType = JobType::firstOrCreate(['name' => 'Brake Pad Replacement']);

        MaintenanceAlert::create([
            'vehicle_id'          => $this->vehicle1->id,
            'job_type_id'         => $jobType->id,
            'alert_type'          => 'Brake System Inspection',
            'urgency'             => 'high',
            'recommendation'      => 'Front brake rotor thickness below critical safety minimum. Immediate overhaul required.',
            'predicted_due_date'  => now()->addDays(2)->toDateString(),
            'predicted_by_algo'   => 'ensemble',
            'confidence_score'    => 92.5,
            'agreement_rate'      => '5/5',
            'source'              => 'predicted',
            'is_read'             => false,
        ]);

        $response = $this->actingAs($this->primaryPic)
            ->get(route('client.fleet-health-report'));

        $response->assertStatus(200);
        $response->assertSee('Fleet Health & Predictive Maintenance Report', false);
        $response->assertSee('Swift Haulage Logistics Sdn Bhd');
        $response->assertSee('TERAJU SETIA ENTERPRISE');
        $response->assertSee('Dr. Nureize');
        $response->assertSee('SWIFT-8001');
        $response->assertSee('High Urgency');
        $response->assertSee('Print Official Report');
    }

    public function test_corporate_client_can_download_fleet_health_report_csv(): void
    {
        MaintenanceAlert::create([
            'vehicle_id'          => $this->vehicle1->id,
            'alert_type'          => 'Engine Oil Degradation Warning',
            'urgency'             => 'medium',
            'recommendation'      => 'Schedule oil and filter replacement within 14 days.',
            'predicted_due_date'  => now()->addDays(10)->toDateString(),
            'predicted_by_algo'   => 'ensemble',
            'confidence_score'    => 87.0,
            'agreement_rate'      => '4/5',
            'source'              => 'predicted',
            'is_read'             => false,
        ]);

        $response = $this->actingAs($this->primaryPic)
            ->get(route('client.fleet-health-report.csv'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');

        // Stream and verify content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('TERAJU SETIA ENTERPRISE — CORPORATE FLEET HEALTH REPORT', $content);
        $this->assertStringContainsString('Swift Haulage Logistics Sdn Bhd', $content);
        $this->assertStringContainsString('[SECTION 1: FLEET HEALTH SUMMARY]', $content);
        $this->assertStringContainsString('[SECTION 2: FLEET VEHICLES RISK ASSESSMENT & PREDICTIONS]', $content);
        $this->assertStringContainsString('[SECTION 3: INVENTORY RESTOCK ADVISORY (RECOMMENDATION ONLY)]', $content);
        $this->assertStringContainsString('SWIFT-8001', $content);
        $this->assertStringContainsString('Advisory restock recommendation only', $content);
        $this->assertStringContainsString('The system never executes automatic purchases', $content);
    }

    public function test_corporate_primary_pic_can_execute_batch_fleet_check(): void
    {
        $response = $this->actingAs($this->primaryPic)
            ->post(route('client.fleet-check.run'), [
                'vehicle_ids' => [$this->vehicle1->id, $this->vehicle2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('maintenance_alerts', [
            'vehicle_id'        => $this->vehicle1->id,
            'source'            => 'predicted',
            'predicted_by_algo' => 'ensemble',
        ]);

        $this->assertDatabaseHas('maintenance_alerts', [
            'vehicle_id'        => $this->vehicle2->id,
            'source'            => 'predicted',
            'predicted_by_algo' => 'ensemble',
        ]);
    }

    public function test_corporate_secondary_pic_cannot_execute_batch_fleet_check(): void
    {
        // Secondary PIC attempting POST should be rejected with 403 Forbidden
        $response = $this->actingAs($this->secondaryPic)
            ->post(route('client.fleet-check.run'), [
                'vehicle_ids' => [$this->vehicle1->id],
            ]);

        $response->assertStatus(403);
    }

    public function test_corporate_secondary_pic_can_view_fleet_check_and_report_in_read_only_mode(): void
    {
        $responseReport = $this->actingAs($this->secondaryPic)
            ->get(route('client.fleet-health-report'));
        $responseReport->assertStatus(200);

        $responseCheck = $this->actingAs($this->secondaryPic)
            ->get(route('client.fleet-check'));
        $responseCheck->assertStatus(200);
        $responseCheck->assertSee('Corporate Viewer Mode (Read-Only)');
        $responseCheck->assertSee('Restricted to Primary PIC');
    }

    public function test_individual_customer_is_forbidden_from_corporate_fleet_package(): void
    {
        // Individual role trying to access corporate fleet endpoints
        $response1 = $this->actingAs($this->individualUser)
            ->get(route('client.fleet-health-report'));
        $response1->assertStatus(403);

        $response2 = $this->actingAs($this->individualUser)
            ->get(route('client.fleet-check'));
        $response2->assertStatus(403);

        $response3 = $this->actingAs($this->individualUser)
            ->post(route('client.fleet-check.run'), []);
        $response3->assertStatus(403);
    }

    public function test_inventory_demand_advisory_calculates_shortfall_and_budget_correctly(): void
    {
        Supplier::create([
            'name'         => 'Brembo Commercial Brake Systems Ltd',
            'contact_name' => 'Tan Wei Meng',
            'phone'        => '+60 3-7788 1122',
            'email'        => 'tan@brembo-my.com',
        ]);

        $part = SparePart::create([
            'name'          => 'Heavy Commercial Brake Pad Set (Front)',
            'part_number'   => 'BRK-PAD-HINO-88',
            'category'      => 'brakes',
            'stock'         => 2,
            'min_stock'     => 10,
            'unit_price'    => 185.50,
            'supplier_name' => 'Brembo Commercial Brake Systems Ltd',
        ]);

        $alert1 = new MaintenanceAlert([
            'vehicle_id'  => $this->vehicle1->id,
            'alert_type'  => 'Brake Pad Replacement Required',
            'urgency'     => 'high',
        ]);

        $alert2 = new MaintenanceAlert([
            'vehicle_id'  => $this->vehicle2->id,
            'alert_type'  => 'Front Brake System Wear Warning',
            'urgency'     => 'medium',
        ]);

        $advisoryService = new InventoryDemandAdvisoryService();
        $advisory = $advisoryService->generateAdvisory(collect([$alert1, $alert2]));

        $this->assertEquals(2, $advisory['active_alert_count']);
        $this->assertEquals(1, $advisory['high_urgency_count']);
        $this->assertEquals(1, $advisory['medium_urgency_count']);

        // Check advisory item for the brake pad
        $brakeItem = collect($advisory['advisory_items'])->firstWhere('part_id', $part->id);
        $this->assertNotNull($brakeItem);
        $this->assertGreaterThanOrEqual(1, $brakeItem['recommended_qty']);
        $this->assertTrue($brakeItem['needs_restock']);
        $this->assertGreaterThan(0, $brakeItem['shortfall_units']);
        $this->assertEquals(
            round($brakeItem['shortfall_units'] * 185.50, 2),
            $brakeItem['estimated_cost']
        );
        $this->assertEquals('Brembo Commercial Brake Systems Ltd', $brakeItem['supplier_name']);
        $this->assertStringContainsString('Advisory restock recommendation only', $advisory['disclaimer']);
        $this->assertStringContainsString('The system never executes automatic purchases', $advisory['disclaimer']);
    }
}
