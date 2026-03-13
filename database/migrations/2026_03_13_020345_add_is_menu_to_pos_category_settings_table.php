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
        Schema::table('pos_category_settings', function (Blueprint $table) {
            $table->boolean('is_menu')->default(false)->after('show_in_pos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_category_settings', function (Blueprint $table) {
            $table->dropColumn('is_menu');
        });
    }
};
