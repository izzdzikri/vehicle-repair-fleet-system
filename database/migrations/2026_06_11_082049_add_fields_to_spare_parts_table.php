<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('spare_parts', function (Blueprint $table) {
            $table->string('category', 50)->default('general')->after('name');
            $table->string('brand', 50)->nullable()->after('category');
            $table->integer('max_stock')->default(100)->after('min_stock');
            $table->date('expiry_date')->nullable()->after('unit_price');
            $table->date('manufacture_date')->nullable()->after('expiry_date');
            $table->string('supplier_name', 100)->nullable()->after('manufacture_date');
            $table->string('supplier_contact', 50)->nullable()->after('supplier_name');
            $table->boolean('is_special_order')->default(false)->after('supplier_contact');
        });
    }
    public function down(): void {
        Schema::table('spare_parts', function (Blueprint $table) {
            $table->dropColumn([
                'category','brand','max_stock','expiry_date',
                'manufacture_date','supplier_name','supplier_contact','is_special_order'
            ]);
        });
    }
};