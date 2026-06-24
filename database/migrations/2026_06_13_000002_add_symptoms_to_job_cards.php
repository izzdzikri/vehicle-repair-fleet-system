<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            // Technician-filled symptom checklist stored as JSON
            $table->text('symptoms')->nullable()->after('diagnosis');
            // Technician notes after diagnosis
            $table->text('technician_notes')->nullable()->after('symptoms');
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn(['symptoms', 'technician_notes']);
        });
    }
};
