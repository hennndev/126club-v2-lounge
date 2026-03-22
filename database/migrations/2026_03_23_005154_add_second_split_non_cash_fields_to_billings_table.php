<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            $table->decimal('split_second_non_cash_amount', 15, 2)
                ->nullable()
                ->after('split_non_cash_reference_number');
            $table->enum('split_second_non_cash_method', ['debit', 'kredit', 'qris', 'transfer', 'ewallet', 'lainnya'])
                ->nullable()
                ->after('split_second_non_cash_amount');
            $table->string('split_second_non_cash_reference_number', 100)
                ->nullable()
                ->after('split_second_non_cash_method');
        });
    }

    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            $table->dropColumn([
                'split_second_non_cash_amount',
                'split_second_non_cash_method',
                'split_second_non_cash_reference_number',
            ]);
        });
    }
};
