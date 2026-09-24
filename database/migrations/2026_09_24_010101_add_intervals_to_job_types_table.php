<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('job_types', function (Blueprint $table) {
            $table->integer('interval_km')->nullable()->after('base_price');
            $table->integer('interval_months')->nullable()->after('interval_km');
        });

        // Seed sensible defaults for the schedule-based service types
        // already in the demo dataset. Wear/fault-triggered types
        // (Brake Pad Replacement, Battery Replacement, Engine
        // Diagnostics Scan) are deliberately left untracked — those are
        // triggered by inspection findings, not a fixed schedule, so a
        // km/time interval would be misleading rather than helpful.
        $defaults = [
            'Full Engine Service'       => ['km' => 10000, 'months' => 12],
            'Oil & Filter Change'       => ['km' => 5000,  'months' => 6],
            'Tyre Rotation & Balancing' => ['km' => 10000, 'months' => 6],
            'Air Conditioning Service'  => ['km' => null,  'months' => 24],
            'Suspension Inspection'     => ['km' => 20000, 'months' => 24],
            'Transmission Fluid Change' => ['km' => 40000, 'months' => 24],
            'Full Vehicle Inspection'   => ['km' => null,  'months' => 12],
        ];

        foreach ($defaults as $name => $interval) {
            DB::table('job_types')->where('name', $name)->update([
                'interval_km'     => $interval['km'],
                'interval_months' => $interval['months'],
            ]);
        }
    }

    public function down(): void {
        Schema::table('job_types', function (Blueprint $table) {
            $table->dropColumn(['interval_km', 'interval_months']);
        });
    }
};