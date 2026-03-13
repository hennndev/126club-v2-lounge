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
        Schema::table('billings', function (Blueprint $table) {
            $table->enum('payment_mode', ['normal', 'split'])->nullable()->after('payment_method');
            $table->decimal('split_cash_amount', 15, 2)->nullable()->after('payment_mode');
            $table->decimal('split_debit_amount', 15, 2)->nullable()->after('split_cash_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            $table->dropColumn(['payment_mode', 'split_cash_amount', 'split_debit_amount']);
        });
    }
};
