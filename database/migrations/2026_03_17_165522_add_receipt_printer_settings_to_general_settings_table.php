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
            $table->foreignId('closed_billing_receipt_printer_id')
                ->nullable()
                ->after('service_charge_percentage')
                ->constrained('printers')
                ->nullOnDelete();

            $table->foreignId('walk_in_receipt_printer_id')
                ->nullable()
                ->after('closed_billing_receipt_printer_id')
                ->constrained('printers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropForeign(['closed_billing_receipt_printer_id']);
            $table->dropForeign(['walk_in_receipt_printer_id']);
            $table->dropColumn(['closed_billing_receipt_printer_id', 'walk_in_receipt_printer_id']);
        });
    }
};
