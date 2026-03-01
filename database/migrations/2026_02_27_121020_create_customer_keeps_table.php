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
        Schema::create('customer_keeps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_user_id')->constrained('customer_users')->onDelete('cascade');
            $table->string('item_name');
            $table->enum('type', ['weekday', 'weekend_event'])->default('weekend_event');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->string('unit')->default('bottle'); // bottle, glass, ml, pcs, etc.
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'used'])->default('active');
            $table->timestamp('stored_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_keeps');
    }
};
