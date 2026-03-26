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
        Schema::create('recap_history_bar', function (Blueprint $table) {
            $table->id();
            $table->date('end_day');
            $table->unsignedBigInteger('total_items')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique('end_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recap_history_bar');
    }
};
