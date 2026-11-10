<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive-only migration — safely adds ML R&D columns to the existing
 * maintenance_alerts table without modifying or removing any existing columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_alerts', function (Blueprint $table) {
            // Guard: only add if column doesn't exist yet
            if (! Schema::hasColumn('maintenance_alerts', 'predicted_by_algo')) {
                $table->string('predicted_by_algo')->nullable()->after('source')
                    ->comment('Algorithm that generated this alert, e.g. random_forest');
            }
            if (! Schema::hasColumn('maintenance_alerts', 'confidence_score')) {
                $table->decimal('confidence_score', 5, 2)->nullable()->after('predicted_by_algo')
                    ->comment('ML confidence 0-100%');
            }
            if (! Schema::hasColumn('maintenance_alerts', 'ml_staff_verified')) {
                $table->enum('ml_staff_verified', ['pending', 'verified', 'rejected'])->default('pending')
                    ->after('confidence_score')
                    ->comment('Staff human-in-the-loop verification status');
            }
            if (! Schema::hasColumn('maintenance_alerts', 'ml_verified_by_user_id')) {
                $table->foreignId('ml_verified_by_user_id')->nullable()
                    ->after('ml_staff_verified')
                    ->constrained('users')->nullOnDelete()
                    ->comment('User who verified/rejected the ML alert');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_alerts', function (Blueprint $table) {
            if (Schema::hasColumn('maintenance_alerts', 'ml_verified_by_user_id')) {
                $table->dropForeign(['ml_verified_by_user_id']);
                $table->dropColumn('ml_verified_by_user_id');
            }
            foreach (['predicted_by_algo', 'confidence_score', 'ml_staff_verified'] as $col) {
                if (Schema::hasColumn('maintenance_alerts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
