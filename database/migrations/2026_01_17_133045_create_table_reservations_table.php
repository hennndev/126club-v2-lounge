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
        Schema::create('table_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_code')->unique();
            $table->foreignId('table_id')->constrained('tables')->onDelete('restrict');
            $table->foreignId('customer_id')->constrained('users')->onDelete('restrict');
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'rejected', 'force_closed'])->default('pending');
            $table->timestamps();

            // Indexes
            $table->index('table_id');
            $table->index('reservation_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_reservations');
    }
};
