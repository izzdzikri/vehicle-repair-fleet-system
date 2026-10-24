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
        if (Schema::hasTable('leave_requests') && !Schema::hasColumn('leave_requests', 'leave_type')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->string('leave_type', 30)->default('annual')->after('staff_id');
            });
        }

        if (Schema::hasTable('salary_payments')) {
            Schema::table('salary_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('salary_payments', 'base_salary')) {
                    $table->decimal('base_salary', 10, 2)->nullable()->after('amount');
                }
                if (!Schema::hasColumn('salary_payments', 'allowances')) {
                    $table->decimal('allowances', 10, 2)->default(0)->after('base_salary');
                }
                if (!Schema::hasColumn('salary_payments', 'deductions')) {
                    $table->decimal('deductions', 10, 2)->default(0)->after('allowances');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'leave_type')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropColumn('leave_type');
            });
        }

        if (Schema::hasTable('salary_payments')) {
            Schema::table('salary_payments', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('salary_payments', 'base_salary')) $cols[] = 'base_salary';
                if (Schema::hasColumn('salary_payments', 'allowances')) $cols[] = 'allowances';
                if (Schema::hasColumn('salary_payments', 'deductions')) $cols[] = 'deductions';
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
