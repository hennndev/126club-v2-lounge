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
            $table->string('transaction_code')->nullable()->after('billing_status');
            $table->enum('payment_method', ['cash', 'kredit', 'debit'])->nullable()->after('transaction_code');
        });
    }

    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            $table->dropColumn(['transaction_code', 'payment_method']);
        });
    }
};
