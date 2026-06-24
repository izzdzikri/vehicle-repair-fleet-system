<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('spare_parts', function (Blueprint $table) {
        $table->id();
        $table->string('name', 100);
        $table->string('part_number', 50)->unique();
        $table->integer('stock')->default(0);
        $table->integer('min_stock')->default(5);
        $table->decimal('unit_price', 10, 2);
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('spare_parts'); }
};
