<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billings', function (Blueprint $table): void {
            $table->string('foc_comp_payment_method', 20)->nullable()->after('payment_mode');
        });
    }

    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table): void {
            $table->dropColumn('foc_comp_payment_method');
        });
    }
};
