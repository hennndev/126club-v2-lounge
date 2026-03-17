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
        Schema::table('dashboard', function (Blueprint $table) {
            $table->unsignedBigInteger('total_kitchen_items')->default(0);
            $table->unsignedBigInteger('total_bar_items')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboard', function (Blueprint $table) {
            $table->dropColumn(['total_kitchen_items', 'total_bar_items']);
        });
    }
};
