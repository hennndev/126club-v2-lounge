<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('receipt_print_count')->default(0)->after('accurate_inv_number');
            $table->unsignedInteger('kitchen_print_count')->default(0)->after('receipt_print_count');
            $table->unsignedInteger('bar_print_count')->default(0)->after('kitchen_print_count');
            $table->unsignedInteger('checker_print_count')->default(0)->after('bar_print_count');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_print_count',
                'kitchen_print_count',
                'bar_print_count',
                'checker_print_count',
            ]);
        });
    }
};
