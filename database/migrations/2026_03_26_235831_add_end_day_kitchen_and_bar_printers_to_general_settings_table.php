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
            $table->foreignId('end_day_kitchen_printer_id')
                ->nullable()
                ->after('end_day_receipt_printer_id')
                ->constrained('printers')
                ->nullOnDelete();

            $table->foreignId('end_day_bar_printer_id')
                ->nullable()
                ->after('end_day_kitchen_printer_id')
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
            $table->dropForeign(['end_day_kitchen_printer_id']);
            $table->dropForeign(['end_day_bar_printer_id']);
            $table->dropColumn(['end_day_kitchen_printer_id', 'end_day_bar_printer_id']);
        });
    }
};
