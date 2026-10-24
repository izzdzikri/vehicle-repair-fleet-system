<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('maintenance_alerts', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_read');
            $table->foreignId('verified_by')->nullable()->after('is_verified')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('admin_notes')->nullable()->after('verified_at');
            $table->date('overridden_due_date')->nullable()->after('admin_notes');
            $table->enum('overridden_urgency', ['low', 'medium', 'high'])->nullable()->after('overridden_due_date');
        });
    }

    public function down(): void {
        Schema::table('maintenance_alerts', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'is_verified',
                'verified_by',
                'verified_at',
                'admin_notes',
                'overridden_due_date',
                'overridden_urgency',
            ]);
        });
    }
};
