<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('category', 50)->default('general');
            $table->integer('estimated_minutes')->default(60);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('job_types');
    }
};