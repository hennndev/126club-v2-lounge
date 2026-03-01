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
        Schema::table('bar_order_items', function (Blueprint $table) {
            // Make nullable — beverages are direct inventory items with no BOM recipe
            $table->foreignId('bom_recipe_id')->nullable()->change();
            // Add direct inventory_item reference for non-BOM beverages
            $table->foreignId('inventory_item_id')->nullable()->after('bom_recipe_id')->constrained('inventory_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bar_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_item_id');
            $table->foreignId('bom_recipe_id')->nullable(false)->change();
        });
    }
};
