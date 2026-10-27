<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['status', 'date'], 'idx_appointments_status_date');
        });

        Schema::table('job_cards', function (Blueprint $table) {
            $table->index(['current_stage', 'updated_at'], 'idx_job_cards_stage_updated');
            $table->index(['staff_id', 'current_stage'], 'idx_job_cards_staff_stage');
            $table->index('completed_at', 'idx_job_cards_completed_at');
        });

        Schema::table('maintenance_alerts', function (Blueprint $table) {
            $table->index(['is_read', 'urgency'], 'idx_alerts_read_urgency');
            $table->index(['vehicle_id', 'is_read'], 'idx_alerts_vehicle_read');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('status', 'idx_invoices_status');
            $table->index('due_date', 'idx_invoices_due_date');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read'], 'idx_notifications_user_read');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'idx_users_role');
            $table->index('status', 'idx_users_status');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_status');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_status');
            $table->dropIndex('idx_invoices_due_date');
        });

        Schema::table('maintenance_alerts', function (Blueprint $table) {
            $table->dropIndex('idx_alerts_read_urgency');
            $table->dropIndex('idx_alerts_vehicle_read');
        });

        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropIndex('idx_job_cards_stage_updated');
            $table->dropIndex('idx_job_cards_staff_stage');
            $table->dropIndex('idx_job_cards_completed_at');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_status_date');
        });
    }
};
