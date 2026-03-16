<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            $table->dropForeign(['table_session_id']);
            $table->foreignId('table_session_id')->nullable()->change();
            $table->foreign('table_session_id')->references('id')->on('table_sessions')->onDelete('cascade');

            $table->foreignId('order_id')->nullable()->unique()->after('table_session_id')->constrained('orders')->nullOnDelete();
            $table->boolean('is_walk_in')->default(false)->after('order_id');
            $table->boolean('is_booking')->default(true)->after('is_walk_in');
        });

        DB::table('billings')
            ->whereNotNull('table_session_id')
            ->update([
                'is_booking' => true,
                'is_walk_in' => false,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['table_session_id']);

            $table->dropColumn(['order_id', 'is_walk_in', 'is_booking']);

            $table->foreignId('table_session_id')->nullable(false)->change();
            $table->foreign('table_session_id')->references('id')->on('table_sessions')->onDelete('cascade');
        });
    }
};
