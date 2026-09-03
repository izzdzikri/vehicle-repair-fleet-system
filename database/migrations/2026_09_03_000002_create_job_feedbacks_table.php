<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('job_feedbacks');
    }
};