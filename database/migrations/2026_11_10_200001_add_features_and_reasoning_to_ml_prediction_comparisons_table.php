<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ml_prediction_comparisons', function (Blueprint $table) {
            $table->json('features_json')->nullable()->after('agreement_rate');
            $table->json('reasoning_json')->nullable()->after('features_json');
        });
    }

    public function down(): void
    {
        Schema::table('ml_prediction_comparisons', function (Blueprint $table) {
            $table->dropColumn(['features_json', 'reasoning_json']);
        });
    }
};
