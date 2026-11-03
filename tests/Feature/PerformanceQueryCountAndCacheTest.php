<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobCard;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceQueryCountAndCacheTest extends TestCase
{
    use DatabaseTransactions;

    public function test_job_cards_index_has_bounded_query_count(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get('/admin/job-cards');

        $response->assertStatus(200);
        $queries = DB::getQueryLog();
        $this->assertLessThanOrEqual(15, count($queries), 'Job cards index exceeded query budget.');
    }

    public function test_coordinator_job_cards_has_bounded_query_count(): void
    {
        $coordinator = User::where('role', 'coordinator')->first() ?? User::factory()->create(['role' => 'coordinator']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($coordinator)->get('/coordinator/job-cards');

        $response->assertStatus(200);
        $queries = DB::getQueryLog();
        $this->assertLessThanOrEqual(15, count($queries), 'Coordinator job cards exceeded query budget.');
    }

    public function test_admin_dashboard_warm_cache_has_bounded_query_count(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        // Prime the cache
        $this->actingAs($admin)->get('/admin/dashboard')->assertStatus(200);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $queries = DB::getQueryLog();
        $this->assertLessThanOrEqual(30, count($queries), 'Admin dashboard exceeded warm-cache query budget.');
    }

    public function test_corporate_trip_logs_has_bounded_query_count(): void
    {
        $corporate = User::where('role', 'corporate')->whereNotNull('company_id')->first();
        if (! $corporate) {
            $company = Company::factory()->create();
            $corporate = User::factory()->create(['role' => 'corporate', 'company_id' => $company->id]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($corporate)->get('/client/trip-logs');

        $response->assertStatus(200);
        $queries = DB::getQueryLog();
        $this->assertLessThanOrEqual(15, count($queries), 'Corporate trip logs exceeded query budget.');
    }

    public function test_admin_vehicles_index_has_bounded_query_count(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get('/admin/vehicles');

        $response->assertStatus(200);
        $queries = DB::getQueryLog();
        $this->assertLessThanOrEqual(10, count($queries), 'Admin vehicles list exceeded query budget.');
    }

    public function test_cache_invalidation_when_job_card_stage_changes(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        $jobCard = JobCard::where('current_stage', 'quality_check')->first() ?? JobCard::factory()->create(['current_stage' => 'quality_check']);

        Cache::put('admin_demand_forecast_and_savings_60', ['cached' => true], 1800);
        $this->assertTrue(Cache::has('admin_demand_forecast_and_savings_60'));

        $response = $this->actingAs($admin)->patch("/admin/job-cards/{$jobCard->id}/stage", [
            'current_stage' => 'completed',
        ]);

        $response->assertStatus(302);
        $this->assertFalse(Cache::has('admin_demand_forecast_and_savings_60'), 'Cache was not invalidated upon job card completion.');
    }

    public function test_corporate_tenant_isolation_prevents_cross_tenant_data_leakage(): void
    {
        $companyA = Company::factory()->create(['name' => 'Company Alpha']);
        $companyB = Company::factory()->create(['name' => 'Company Beta']);

        $userA = User::factory()->create(['role' => 'corporate', 'company_id' => $companyA->id]);
        $userB = User::factory()->create(['role' => 'corporate', 'company_id' => $companyB->id]);

        $vehicleB = Vehicle::factory()->create(['user_id' => $userB->id]);

        // User A cannot view User B's vehicle
        $this->actingAs($userA)
            ->get("/client/vehicles/{$vehicleB->id}")
            ->assertStatus(403);

        // User A cannot see User B's company data
        $response = $this->actingAs($userA)->get('/client/company');
        $response->assertStatus(200);
        $response->assertSee('Company Alpha');
        $response->assertDontSee('Company Beta');
    }
}
