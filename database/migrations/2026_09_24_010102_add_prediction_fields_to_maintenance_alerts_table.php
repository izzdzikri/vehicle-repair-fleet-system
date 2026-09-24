<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('maintenance_alerts', function (Blueprint $table) {
            $table->enum('source', ['manual', 'predicted'])->default('manual')->after('vehicle_id');
            $table->foreignId('job_type_id')->nullable()->after('alert_type')->constrained()->nullOnDelete();
            $table->date('predicted_due_date')->nullable()->after('recommendation');
        });
    }

    public function down(): void {
        Schema::table('maintenance_alerts', function (Blueprint $table) {
            $table->dropForeign(['job_type_id']);
            $table->dropColumn(['source', 'job_type_id', 'predicted_due_date']);
        });
    }
};