<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','staff','corporate','individual','coordinator') NOT NULL DEFAULT 'individual'");
    }
    public function down(): void {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','staff','corporate','individual') NOT NULL DEFAULT 'individual'");
    }
};