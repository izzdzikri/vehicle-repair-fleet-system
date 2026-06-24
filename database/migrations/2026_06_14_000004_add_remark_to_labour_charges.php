<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('labour_charges', function (Blueprint $table) {
            $table->string('remark', 200)->nullable()->after('charge');
        });
    }
    public function down(): void {
        Schema::table('labour_charges', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};