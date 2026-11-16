<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ml_prediction_comparisons', function (Blueprint $table) {
            // Logistic Regression predictions
            $table->enum('lr_prediction', ['LOW', 'MEDIUM', 'HIGH'])->nullable()->after('svm_confidence');
            $table->decimal('lr_confidence', 5, 2)->nullable()->after('lr_prediction');
        });
    }

    public function down(): void
    {
        Schema::table('ml_prediction_comparisons', function (Blueprint $table) {
            $table->dropColumn(['lr_prediction', 'lr_confidence']);
        });
    }
};
