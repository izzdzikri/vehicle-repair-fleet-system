<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modify the enum to add 'completed'
        // We use a raw statement because Laravel's Blueprint doesn't support
        // changing an existing enum without dropping and recreating it.
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending'");

        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('is_walkin')->default(false)->after('notes');
            // Walk-in appointments don't come from a registered customer,
            // so user_id must be nullable.
            $table->unsignedBigInteger('user_id')->nullable()->change();
            // Walk-in also needs a customer name stored directly.
            $table->string('walkin_name', 100)->nullable()->after('is_walkin');
            $table->string('walkin_contact', 30)->nullable()->after('walkin_name');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['is_walkin', 'walkin_name', 'walkin_contact']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
