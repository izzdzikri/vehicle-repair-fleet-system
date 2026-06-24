<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('appointments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
        $table->date('date');
        $table->time('time');
        $table->string('service_type', 100);
        $table->enum('status', ['pending','confirmed','cancelled'])->default('pending');
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('appointments'); }
};
