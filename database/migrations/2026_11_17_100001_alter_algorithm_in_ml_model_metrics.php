<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ml_model_metrics', function (Blueprint $table) {
            $table->string('algorithm', 50)->change();
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `ml_model_metrics` MODIFY COLUMN `algorithm` ENUM('rule_based', 'decision_tree', 'random_forest', 'svm') NOT NULL");
    }
};
