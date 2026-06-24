<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('account_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', [
                'add_pic',       // request to add a new PIC to the company
                'remove_pic',    // request to remove a PIC
                'close_account', // request to close the company account
                'delete_account',// individual user delete their own account
            ]);
            $table->string('target_name')->nullable();    // new PIC name (for add_pic)
            $table->string('target_email')->nullable();   // new PIC email
            $table->string('target_phone')->nullable();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->onDelete('set null'); // for remove_pic
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('account_requests');
    }
};