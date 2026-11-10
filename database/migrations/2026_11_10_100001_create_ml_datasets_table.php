<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('source_type', ['synthetic', 'kaggle_benchmark', 'system_live', 'unlabeled_test']);
            $table->string('file_path')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->json('feature_mapping_json')->nullable()->comment('Maps uploaded CSV headers to system schema');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_datasets');
    }
};
