<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobType;
use App\Models\MaintenanceAlert;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AlertsUxTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $corporateUser;
    protected User $individualUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->company = Company::create([
            'name' => 'Petronas Logistics Fleet',
        ]);

        $this->corporateUser = User::factory()->create([
            'role'       => 'corporate',
            'company_id' => $this->company->id,
        ]);

        $this->individualUser = User::factory()->create([
            'role' => 'individual',
        ]);
    }

    public function test_plain_language_accessors_format_supervisor_friendly_labels(): void
    {
        $alert = new MaintenanceAlert([
            'confidence_score'    => 93.6,
            'agreement_rate'      => '4/5',
            'ml_staff_verified'   => 'verified',
            'is_verified'         => true,
        ]);

        $this->assertEquals('94% Confidence', $alert->plain_confidence);
        $this->assertEquals('4 of 5 models agreed', $alert->plain_agreement_rate);
        $this->assertEquals('Verified', $alert->plain_verification_status);

        // When agreement rate is 5/5
        $unanimousAlert = new MaintenanceAlert([
            'confidence_score'  => 88.0,
            'agreement_rate'    => '5/5',
            'ml_staff_verified' => 'rejected',
        ]);
        $this->assertEquals('88% Confidence', $unanimousAlert->plain_confidence);
        $this->assertEquals('5 of 5 models agreed', $unanimousAlert->plain_agreement_rate);
        $this->assertEquals('Flagged', $unanimousAlert->plain_verification_status);

        // Default / manual fallback
        $manualAlert = new MaintenanceAlert([]);
        $this->assertEquals('Standard Interval', $manualAlert->plain_confidence);
        $this->assertNull($manualAlert->plain_agreement_rate);
        $this->assertEquals('Pending Review', $manualAlert->plain_verification_status);
    }

    public function test_admin_dashboard_surfaces_top_high_urgency_alerts(): void
    {
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'DASH-HIGH-01',
            'user_id'      => $this->admin->id,
        ]);

        $alert = MaintenanceAlert::create([
            'vehicle_id'          => $vehicle->id,
            'source'              => 'predicted',
            'alert_type'          => 'Emergency Brake Replacement',
            'urgency'             => 'high',
            'recommendation'      => 'Immediate brake pad replacement required.',
            'predicted_due_date'  => now()->subDays(60)->toDateString(),
            'confidence_score'    => 96.0,
            'agreement_rate'      => '5/5',
            'ml_staff_verified'   => 'verified',
            'is_verified'         => true,
            'is_read'             => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('topHighUrgencyAlerts');
        $response->assertSee('Top High-Urgency Fleet Alerts (Immediate Action Required)');
        $response->assertSee('DASH-HIGH-01');
        $response->assertSee('Emergency Brake Replacement');
        $response->assertSee('96% Confidence');
        $response->assertSee('5 of 5 models agreed');
    }

    public function test_compact_sortable_table_renders_plain_language_labels_and_badges(): void
    {
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'WXY-7890',
            'user_id'      => $this->admin->id,
            'brand'        => 'Toyota',
            'model'        => 'Hilux',
        ]);

        MaintenanceAlert::create([
            'vehicle_id'          => $vehicle->id,
            'source'              => 'predicted',
            'alert_type'          => 'Transmission Fluid Service',
            'urgency'             => 'high',
            'recommendation'      => 'Flush transmission fluid within 3 days.',
            'predicted_due_date'  => now()->addDays(3)->toDateString(),
            'confidence_score'    => 91.2,
            'agreement_rate'      => '4/5',
            'ml_staff_verified'   => 'verified',
            'is_verified'         => true,
            'is_read'             => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.maintenance'));

        $response->assertStatus(200);
        $response->assertSee('Fleet Maintenance Log');
        $response->assertSee('WXY-7890');
        $response->assertSee('Transmission Fluid Service');
        $response->assertSee('91% Confidence');
        $response->assertSee('4 of 5 models agreed');
        // Ensure raw model names like "Decision Tree confidence" or "decision_tree" are NOT displayed as confidence badges
        $response->assertDontSee('Decision Tree confidence');
        $response->assertDontSee('Random Forest vote');
    }

    public function test_non_admin_cannot_access_admin_maintenance_route(): void
    {
        $this->actingAs($this->individualUser)
            ->get(route('admin.maintenance'))
            ->assertStatus(403);

        $this->actingAs($this->corporateUser)
            ->get(route('admin.maintenance'))
            ->assertStatus(403);
    }

    public function test_corporate_and_individual_users_see_only_their_vehicles_in_maintenance(): void
    {
        $corpVehicle = Vehicle::factory()->create([
            'plate_number' => 'CORP-101',
            'user_id'      => $this->corporateUser->id,
        ]);

        $indivVehicle = Vehicle::factory()->create([
            'plate_number' => 'INDIV-202',
            'user_id'      => $this->individualUser->id,
        ]);

        MaintenanceAlert::create([
            'vehicle_id'          => $corpVehicle->id,
            'source'              => 'predicted',
            'alert_type'          => 'Fleet Tire Rotation',
            'urgency'             => 'medium',
            'recommendation'      => 'Rotate all 4 wheels.',
            'confidence_score'    => 85.0,
            'agreement_rate'      => '4/5',
            'is_read'             => false,
        ]);

        MaintenanceAlert::create([
            'vehicle_id'          => $indivVehicle->id,
            'source'              => 'predicted',
            'alert_type'          => 'Personal Oil Change',
            'urgency'             => 'low',
            'recommendation'      => 'Schedule regular oil service.',
            'confidence_score'    => 75.0,
            'agreement_rate'      => '3/5',
            'is_read'             => false,
        ]);

        // Corporate user sees only their company's vehicle alert
        $corpResponse = $this->actingAs($this->corporateUser)
            ->get(route('client.maintenance'));
        $corpResponse->assertStatus(200);
        $corpResponse->assertSee('CORP-101');
        $corpResponse->assertDontSee('INDIV-202');

        // Individual customer sees only their own vehicle alert
        $indivResponse = $this->actingAs($this->individualUser)
            ->get(route('customer.maintenance'));
        $indivResponse->assertStatus(200);
        $indivResponse->assertSee('INDIV-202');
        $indivResponse->assertDontSee('CORP-101');
    }
}
