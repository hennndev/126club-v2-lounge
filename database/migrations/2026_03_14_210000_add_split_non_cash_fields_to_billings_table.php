<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billings', function (Blueprint $table): void {
            $table->string('payment_reference_number', 100)->nullable()->after('payment_method');
            $table->enum('split_non_cash_method', ['debit', 'kredit', 'qris', 'transfer', 'ewallet', 'lainnya'])->nullable()->after('split_debit_amount');
            $table->string('split_non_cash_reference_number', 100)->nullable()->after('split_non_cash_method');
        });
    }

    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_reference_number',
                'split_non_cash_method',
                'split_non_cash_reference_number',
            ]);
        });
    }
};
