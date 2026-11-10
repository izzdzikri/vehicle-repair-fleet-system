<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_experiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('ml_datasets')->cascadeOnDelete();
            $table->string('run_name');
            $table->decimal('split_ratio', 4, 2)->default(0.80)->comment('Train split fraction, e.g. 0.80');
            $table->unsignedTinyInteger('cv_folds')->default(5);
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued');
            $table->text('error_message')->nullable();
            $table->json('summary_json')->nullable()->comment('Overall + per-year metrics for all 4 models');
            $table->json('hyperparams_json')->nullable()->comment('User-configured hyperparameters snapshot');
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_experiments');
    }
};
