<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('color', 30)->nullable()->after('mileage');
            $table->string('fuel_type', 20)->nullable()->after('color');
            // Allow null for walk-in vehicles (no registered owner)
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['color', 'fuel_type']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};