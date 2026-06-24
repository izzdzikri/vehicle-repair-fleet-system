<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('account_requests', function (Blueprint $table) {
            $table->enum('pic_role', ['primary','secondary'])->nullable()->after('type');
        });
    }
    public function down(): void {
        Schema::table('account_requests', function (Blueprint $table) {
            $table->dropColumn('pic_role');
        });
    }
};