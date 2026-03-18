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
        Schema::table('recap_history', function (Blueprint $table) {
            $table->unsignedBigInteger('total_kitchen_items')->default(0)->after('total_qris');
            $table->unsignedBigInteger('total_bar_items')->default(0)->after('total_kitchen_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recap_history', function (Blueprint $table) {
            $table->dropColumn(['total_kitchen_items', 'total_bar_items']);
        });
    }
};
