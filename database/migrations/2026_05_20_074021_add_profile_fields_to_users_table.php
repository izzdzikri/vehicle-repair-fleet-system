<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void {
    Schema::table('users', function (Blueprint $table) {
        $table->string('username', 50)->unique()->nullable()->after('name');
        $table->string('avatar', 255)->nullable()->after('contact_no');
    });
}
public function down(): void {
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['username', 'avatar']);
    });
}
};
