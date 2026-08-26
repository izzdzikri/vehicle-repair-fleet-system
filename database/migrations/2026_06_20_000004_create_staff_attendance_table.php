<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('staff_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->enum('status', ['present', 'absent', 'leave', 'half_day'])->default('present');
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->unique(['staff_id', 'date']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('staff_attendance');
    }
};