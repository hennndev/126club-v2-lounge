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
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('checker_area');

            $table->boolean('can_choose_checker')
                ->default(false)
                ->after('service_charge_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('can_choose_checker');

            $table->string('checker_area', 20)
                ->default('checker')
                ->after('service_charge_percentage');
        });
    }
};
