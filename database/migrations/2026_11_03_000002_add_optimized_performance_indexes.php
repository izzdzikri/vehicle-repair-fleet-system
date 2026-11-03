<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['date', 'status'], 'idx_appointments_date_status');
        });

        Schema::table('job_cards', function (Blueprint $table) {
            $table->index(['current_stage', 'estimated_completion'], 'idx_job_cards_stage_est_completion');
            $table->index('estimated_completion', 'idx_job_cards_est_completion');
        });

        Schema::table('service_history', function (Blueprint $table) {
            $table->index(['vehicle_id', 'service_date'], 'idx_service_history_vehicle_date');
            $table->index('service_date', 'idx_service_history_service_date');
        });

        Schema::table('trip_logs', function (Blueprint $table) {
            $table->index(['vehicle_id', 'trip_date'], 'idx_trip_logs_vehicle_date');
        });

        Schema::table('maintenance_alerts', function (Blueprint $table) {
            $table->index(['source', 'predicted_due_date'], 'idx_alerts_source_due_date');
            $table->index(['vehicle_id', 'job_type_id', 'source'], 'idx_alerts_vehicle_job_source');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_alerts', function (Blueprint $table) {
            $table->dropIndex('idx_alerts_source_due_date');
            $table->dropIndex('idx_alerts_vehicle_job_source');
        });

        Schema::table('trip_logs', function (Blueprint $table) {
            $table->dropIndex('idx_trip_logs_vehicle_date');
        });

        Schema::table('service_history', function (Blueprint $table) {
            $table->dropIndex('idx_service_history_vehicle_date');
            $table->dropIndex('idx_service_history_service_date');
        });

        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropIndex('idx_job_cards_stage_est_completion');
            $table->dropIndex('idx_job_cards_est_completion');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_date_status');
        });
    }
};
