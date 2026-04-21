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
            $table->decimal('total_staff_meal', 18, 2)->default(0)->after('total_room');
        });

        Schema::table('recap_history', function (Blueprint $table) {
            $table->decimal('total_staff_meal', 18, 2)->default(0)->after('total_room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboard', function (Blueprint $table) {
            $table->dropColumn('total_staff_meal');
        });

        Schema::table('recap_history', function (Blueprint $table) {
            $table->dropColumn('total_staff_meal');
        });
    }
};
