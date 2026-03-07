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
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_reservation_id')->nullable()->constrained('table_reservations')->onDelete('set null');
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->string('session_code')->unique();
            $table->string('check_in_qr_code')->nullable();
            $table->timestamp('check_in_qr_expires_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->enum('status', ['pending', 'active', 'completed', 'force_closed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes untuk performa query
            $table->index('status');
            $table->index('checked_in_at');
            $table->index(['table_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_sessions');
    }
};
