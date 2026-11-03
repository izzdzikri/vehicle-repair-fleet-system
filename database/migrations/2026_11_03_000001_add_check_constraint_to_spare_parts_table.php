<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE spare_parts ADD CONSTRAINT chk_spare_parts_stock_non_negative CHECK (stock >= 0)');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE spare_parts DROP CHECK chk_spare_parts_stock_non_negative');
        }
    }
};
