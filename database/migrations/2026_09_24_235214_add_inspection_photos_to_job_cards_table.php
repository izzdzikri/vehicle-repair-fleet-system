<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            // Array of {id, path, caption, stage, uploaded_by, uploaded_at}.
            // Stored as JSON on the job card itself — same convention as
            // the existing `symptoms` column — rather than a dedicated
            // table, since nothing in the app ever needs to query photos
            // across job cards.
            $table->json('inspection_photos')->nullable()->after('technician_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn('inspection_photos');
        });
    }
};