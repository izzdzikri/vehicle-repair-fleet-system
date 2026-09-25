<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\JobType;
use App\Models\Notification;
use App\Models\MaintenanceAlert;
use App\Services\MaintenancePredictionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PredictiveNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_prediction_scan_dispatches_notification_to_individual_owner(): void
    {
        $ain = User::where('email', 'ain@gmail.com')->first();
        Notification::where('user_id', $ain->id)->delete();
        MaintenanceAlert::where('source', 'predicted')->delete();

        $service = app(MaintenancePredictionService::class);
        $service->runAll();

        // Ain owns Vehicle 10 which has an Oil & Filter Change due soon
        $notification = Notification::where('user_id', $ain->id)
            ->where('url', '/customer/maintenance')
            ->where('message', 'like', '%VKK 3456: Oil & Filter Change Due%')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Predicted Service Due', $notification->title);
    }

    public function test_prediction_scan_dispatches_notifications_to_all_active_corporate_pics(): void
    {
        $hafiz = User::where('email', 'hafiz@mulialogistik.com.my')->first();
        $fifi  = User::where('email', 'fifi@mulialogistik.com.my')->first();

        Notification::whereIn('user_id', [$hafiz->id, $fifi->id])->delete();
        MaintenanceAlert::where('source', 'predicted')->delete();

        $service = app(MaintenancePredictionService::class);
        $service->runAll();

        // Vehicle 1 (Mulia Logistik) has an Oil & Filter Change predicted due soon
        $hafizNotice = Notification::where('user_id', $hafiz->id)
            ->where('url', '/client/maintenance')
            ->where('message', 'like', '%JQP 1122: Oil & Filter Change Due%')
            ->first();

        $fifiNotice = Notification::where('user_id', $fifi->id)
            ->where('url', '/client/maintenance')
            ->where('message', 'like', '%JQP 1122: Oil & Filter Change Due%')
            ->first();

        $this->assertNotNull($hafizNotice);
        $this->assertNotNull($fifiNotice);
    }

    public function test_prediction_scan_does_not_duplicate_notifications_within_7_days(): void
    {
        $ain = User::where('email', 'ain@gmail.com')->first();
        $service = app(MaintenancePredictionService::class);

        // First run
        $service->runAll();
        $initialCount = Notification::where('user_id', $ain->id)
            ->where('message', 'like', '%VKK 3456: Oil & Filter Change Due%')
            ->count();

        // Immediate second run should not duplicate
        $service->runAll();
        $secondCount = Notification::where('user_id', $ain->id)
            ->where('message', 'like', '%VKK 3456: Oil & Filter Change Due%')
            ->count();

        $this->assertEquals($initialCount, $secondCount);
    }

    public function test_escalation_to_high_urgency_dispatches_urgent_notification(): void
    {
        $ain = User::where('email', 'ain@gmail.com')->first();
        $vehicle = Vehicle::where('plate_number', 'VKK 3456')->first();
        $jobType = JobType::where('name', 'Oil & Filter Change')->first();

        // Create initial medium urgency alert with a notification sent 2 days ago
        Notification::create([
            'user_id'    => $ain->id,
            'title'      => '🔮 Predicted Service Due',
            'message'    => "VKK 3456: {$jobType->name} Due — predicted due soon",
            'url'        => '/customer/maintenance',
            'created_at' => now()->subDays(2),
        ]);

        $alert = MaintenanceAlert::where('vehicle_id', $vehicle->id)
            ->where('job_type_id', $jobType->id)
            ->first();

        if ($alert) {
            $alert->update(['urgency' => 'medium']);
        }

        // Trigger escalation by running scan or calling service
        $service = app(MaintenancePredictionService::class);

        // Manually simulate alert transition with overdue date (high urgency)
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('predictAndStore');
        $method->setAccessible(true);

        // Daily rate of 1000 km/day will make it overdue immediately
        $method->invoke($service, $vehicle, $jobType, 1000.0);

        // Urgent notification should have been dispatched despite the recent medium notice
        $urgentNotice = Notification::where('user_id', $ain->id)
            ->where('title', 'like', '%Urgent%')
            ->where('message', 'like', '%VKK 3456: Oil & Filter Change Due%')
            ->first();

        $this->assertNotNull($urgentNotice);
    }
}
