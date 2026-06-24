<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->foreignId('job_type_id')->nullable()->constrained()->nullOnDelete()->after('staff_id');
            $table->timestamp('estimated_completion')->nullable()->after('job_type_id');
        });
    }
    public function down(): void {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropForeign(['job_type_id']);
            $table->dropColumn(['job_type_id', 'estimated_completion']);
        });
    }
};