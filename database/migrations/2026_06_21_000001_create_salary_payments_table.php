<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->string('period', 7); // e.g. '2026-06'
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'bank_transfer', 'cheque'])->default('bank_transfer');
            $table->date('paid_at');
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['staff_id', 'period']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('salary_payments');
    }
};