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
        Schema::table('dashboard', function (Blueprint $table): void {
            $table->unsignedInteger('total_ld_quantity')->default(0)->after('total_ld');
        });

        Schema::table('recap_history', function (Blueprint $table): void {
            $table->unsignedInteger('total_ld_quantity')->default(0)->after('total_ld');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboard', function (Blueprint $table): void {
            $table->dropColumn('total_ld_quantity');
        });

        Schema::table('recap_history', function (Blueprint $table): void {
            $table->dropColumn('total_ld_quantity');
        });
    }
};
