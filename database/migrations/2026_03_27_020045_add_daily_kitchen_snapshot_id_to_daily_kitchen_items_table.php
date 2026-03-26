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
        if (! Schema::hasColumn('daily_kitchen_items', 'daily_kitchen_snapshot_id')) {
            Schema::table('daily_kitchen_items', function (Blueprint $table) {
                $table->unsignedBigInteger('daily_kitchen_snapshot_id')->nullable()->after('id');
            });
        }

        try {
            Schema::table('daily_kitchen_items', function (Blueprint $table) {
                $table->foreign('daily_kitchen_snapshot_id', 'dki_snapshot_fk')
                    ->references('id')
                    ->on('daily_kitchen_snapshots')
                    ->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
        }

        $groupedRows = DB::table('daily_kitchen_items')
            ->selectRaw('end_day, SUM(quantity) as total_items, MAX(updated_at) as last_synced_at')
            ->groupBy('end_day')
            ->get();

        foreach ($groupedRows as $row) {
            $snapshotId = DB::table('daily_kitchen_snapshots')->insertGetId([
                'end_day' => $row->end_day,
                'total_items' => (int) $row->total_items,
                'last_synced_at' => $row->last_synced_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('daily_kitchen_items')
                ->where('end_day', $row->end_day)
                ->update(['daily_kitchen_snapshot_id' => $snapshotId]);
        }

        try {
            Schema::table('daily_kitchen_items', function (Blueprint $table) {
                $table->dropUnique('daily_kitchen_items_end_day_inventory_item_id_unique');
            });
        } catch (\Throwable $e) {
            try {
                Schema::table('daily_kitchen_items', function (Blueprint $table) {
                    $table->dropUnique(['end_day', 'inventory_item_id']);
                });
            } catch (\Throwable $e) {
            }
        }

        try {
            Schema::table('daily_kitchen_items', function (Blueprint $table) {
                $table->unique(['daily_kitchen_snapshot_id', 'inventory_item_id'], 'dki_snapshot_inventory_unique');
            });
        } catch (\Throwable $e) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('daily_kitchen_items', function (Blueprint $table) {
                $table->dropUnique('dki_snapshot_inventory_unique');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('daily_kitchen_items', function (Blueprint $table) {
                $table->dropForeign('dki_snapshot_fk');
            });
        } catch (\Throwable $e) {
            try {
                Schema::table('daily_kitchen_items', function (Blueprint $table) {
                    $table->dropForeign(['daily_kitchen_snapshot_id']);
                });
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasColumn('daily_kitchen_items', 'daily_kitchen_snapshot_id')) {
            Schema::table('daily_kitchen_items', function (Blueprint $table) {
                $table->dropColumn('daily_kitchen_snapshot_id');
            });
        }

        try {
            Schema::table('daily_kitchen_items', function (Blueprint $table) {
                $table->unique(['end_day', 'inventory_item_id'], 'daily_kitchen_items_end_day_inventory_item_id_unique');
            });
        } catch (\Throwable $e) {
        }

        DB::table('daily_kitchen_snapshots')->delete();
    }
};
