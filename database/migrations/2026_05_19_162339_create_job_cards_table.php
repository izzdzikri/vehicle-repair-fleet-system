<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('job_cards', function (Blueprint $table) {
        $table->id();
        $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
        $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
        $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
        $table->enum('current_stage', [
            'received','diagnosing','waiting_parts',
            'repairing','quality_check','completed'
        ])->default('received');
        $table->text('diagnosis')->nullable();
        $table->decimal('total_cost', 10, 2)->default(0);
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('job_cards'); }
};
