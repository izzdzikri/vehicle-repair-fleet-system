<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_card_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coordinator_id')->constrained('users')->cascadeOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('job_card_checkins');
    }
};