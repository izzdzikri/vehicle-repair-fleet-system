<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_alerts', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenance_alerts', 'agreement_rate')) {
                $table->string('agreement_rate', 50)->nullable()->after('confidence_score')
                    ->comment('Multi-model agreement rate, e.g. "4/5 models"');
            }
            if (! Schema::hasColumn('maintenance_alerts', 'ensemble_votes_json')) {
                $table->json('ensemble_votes_json')->nullable()->after('agreement_rate')
                    ->comment('Individual model votes breakdown');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_alerts', function (Blueprint $table) {
            if (Schema::hasColumn('maintenance_alerts', 'ensemble_votes_json')) {
                $table->dropColumn('ensemble_votes_json');
            }
            if (Schema::hasColumn('maintenance_alerts', 'agreement_rate')) {
                $table->dropColumn('agreement_rate');
            }
        });
    }
};
