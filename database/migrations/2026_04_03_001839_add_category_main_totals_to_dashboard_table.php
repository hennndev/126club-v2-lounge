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
            $table->decimal('total_food', 18, 2)->default(0)->after('total_amount');
            $table->decimal('total_alcohol', 18, 2)->default(0)->after('total_food');
            $table->decimal('total_beverage', 18, 2)->default(0)->after('total_alcohol');
            $table->decimal('total_cigarette', 18, 2)->default(0)->after('total_beverage');
            $table->decimal('total_breakage', 18, 2)->default(0)->after('total_cigarette');
            $table->decimal('total_room', 18, 2)->default(0)->after('total_breakage');
            $table->decimal('total_ld', 18, 2)->default(0)->after('total_room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboard', function (Blueprint $table) {
            $table->dropColumn([
                'total_food',
                'total_alcohol',
                'total_beverage',
                'total_cigarette',
                'total_breakage',
                'total_room',
                'total_ld',
            ]);
        });
    }
};
