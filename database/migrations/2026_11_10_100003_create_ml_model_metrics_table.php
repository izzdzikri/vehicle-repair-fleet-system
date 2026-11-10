<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_model_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('ml_experiments')->cascadeOnDelete();
            $table->enum('algorithm', ['rule_based', 'decision_tree', 'random_forest', 'svm']);
            $table->decimal('accuracy', 6, 4)->nullable();
            $table->decimal('precision_macro', 6, 4)->nullable();
            $table->decimal('recall_macro', 6, 4)->nullable();
            $table->decimal('f1_macro', 6, 4)->nullable();
            $table->decimal('high_urgency_recall', 6, 4)->nullable()->comment('Per-class recall for HIGH label');
            $table->json('confusion_matrix_json')->nullable()->comment('3x3 matrix: [[LL,LM,LH],[ML,MM,MH],[HL,HM,HH]]');
            $table->json('yearly_accuracy_json')->nullable()->comment('{"2024":89.2,"2025":91.4,"overall":90.5}');
            $table->json('extra_meta_json')->nullable()->comment('Feature importances, OOB score, tree depth, etc.');
            $table->timestamps();

            $table->unique(['experiment_id', 'algorithm']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_model_metrics');
    }
};
