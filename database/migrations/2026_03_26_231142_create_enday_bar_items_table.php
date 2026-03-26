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
        Schema::create('enday_bar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recap_history_bar_id')->constrained('recap_history_bar')->cascadeOnDelete();
            $table->date('end_day');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedBigInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['end_day', 'inventory_item_id']);
            $table->index('end_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enday_bar_items');
    }
};
