<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('vehicles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('plate_number', 20)->unique();
        $table->string('brand', 50);
        $table->string('model', 50);
        $table->year('year');
        $table->decimal('mileage', 10, 2)->default(0);
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('vehicles'); }
};
