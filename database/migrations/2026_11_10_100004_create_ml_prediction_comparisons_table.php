<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_prediction_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('ml_experiments')->cascadeOnDelete();
            $table->string('vehicle_identifier')->comment('plate_number or synthetic ID');
            $table->string('service_type');
            $table->smallInteger('scan_year')->nullable();
            $table->enum('ground_truth_label', ['LOW', 'MEDIUM', 'HIGH'])->nullable();

            // Rule-Based predictions
            $table->enum('rb_prediction', ['LOW', 'MEDIUM', 'HIGH'])->nullable();
            $table->decimal('rb_confidence', 5, 2)->nullable();
            $table->integer('rb_days_remaining')->nullable();

            // Decision Tree predictions
            $table->enum('dt_prediction', ['LOW', 'MEDIUM', 'HIGH'])->nullable();
            $table->decimal('dt_confidence', 5, 2)->nullable();

            // Random Forest predictions
            $table->enum('rf_prediction', ['LOW', 'MEDIUM', 'HIGH'])->nullable();
            $table->decimal('rf_confidence', 5, 2)->nullable();

            // SVM predictions
            $table->enum('svm_prediction', ['LOW', 'MEDIUM', 'HIGH'])->nullable();
            $table->decimal('svm_confidence', 5, 2)->nullable();

            // Consensus
            $table->enum('consensus_label', ['LOW', 'MEDIUM', 'HIGH'])->nullable();
            $table->string('agreement_rate', 10)->nullable()->comment('e.g. "4/4" or "3/4"');

            // Push to live alerts
            $table->boolean('pushed_to_alerts')->default(false);
            $table->foreignId('pushed_alert_id')->nullable()->constrained('maintenance_alerts')->nullOnDelete();
            $table->string('pushed_algo')->nullable()->comment('Which algo was chosen for the live alert');

            // Human-in-the-loop verification
            $table->enum('staff_verification_status', ['pending', 'verified_correct', 'verified_incorrect'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('staff_notes')->nullable();

            $table->timestamps();

            $table->index(['experiment_id', 'scan_year']);
            $table->index('staff_verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_prediction_comparisons');
    }
};
