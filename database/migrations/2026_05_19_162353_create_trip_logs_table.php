<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('trip_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
        $table->date('trip_date');
        $table->decimal('distance_km', 10, 2);
        $table->string('terrain_type', 50)->default('urban');
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('trip_logs'); }
};
