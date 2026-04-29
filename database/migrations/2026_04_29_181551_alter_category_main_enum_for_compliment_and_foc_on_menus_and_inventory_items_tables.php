<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_items MODIFY COLUMN category_main ENUM('food','alcohol','beverage','cigarette','breakage','room','staff_meal','compliment','foc','LD') NULL");
            DB::statement("ALTER TABLE menus MODIFY COLUMN category_main ENUM('food','alcohol','beverage','cigarette','breakage','room','staff_meal','compliment','foc','LD') NULL");

            return;
        }

        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->string('category_main', 50)->nullable()->change();
        });

        Schema::table('menus', function (Blueprint $table): void {
            $table->string('category_main', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_items MODIFY COLUMN category_main ENUM('food','alcohol','beverage','cigarette','breakage','room','staff_meal','LD') NULL");
            DB::statement("ALTER TABLE menus MODIFY COLUMN category_main ENUM('food','alcohol','beverage','cigarette','breakage','room','staff_meal','LD') NULL");

            return;
        }

        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->string('category_main', 50)->nullable()->change();
        });

        Schema::table('menus', function (Blueprint $table): void {
            $table->string('category_main', 50)->nullable()->change();
        });
    }
};
