<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('estimated_completion');
        });
    }
    public function down(): void {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};