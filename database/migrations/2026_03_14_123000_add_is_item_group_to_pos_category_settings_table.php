<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_category_settings', function (Blueprint $table) {
            $table->boolean('is_item_group')->default(false)->after('is_menu');
        });
    }

    public function down(): void
    {
        Schema::table('pos_category_settings', function (Blueprint $table) {
            $table->dropColumn('is_item_group');
        });
    }
};
