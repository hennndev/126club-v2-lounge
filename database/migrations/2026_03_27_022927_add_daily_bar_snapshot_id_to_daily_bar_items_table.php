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
        if (! Schema::hasTable('daily_bar_snapshots')) {
            Schema::create('daily_bar_snapshots', function (Blueprint $table) {
                $table->id();
                $table->date('end_day')->index();
                $table->unsignedInteger('total_items')->default(0);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('daily_bar_items', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_bar_items', 'daily_bar_snapshot_id')) {
                $table->foreignId('daily_bar_snapshot_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('daily_bar_snapshots')
                    ->cascadeOnDelete();
            }
        });

        $groupedRows = DB::table('daily_bar_items')
            ->selectRaw('end_day, SUM(quantity) as total_items, MAX(updated_at) as last_synced_at')
            ->groupBy('end_day')
            ->get();

        foreach ($groupedRows as $row) {
            $snapshotId = DB::table('daily_bar_snapshots')->insertGetId([
                'end_day' => $row->end_day,
                'total_items' => (int) $row->total_items,
                'last_synced_at' => $row->last_synced_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('daily_bar_items')
                ->where('end_day', $row->end_day)
                ->update(['daily_bar_snapshot_id' => $snapshotId]);
        }

        try {
            Schema::table('daily_bar_items', function (Blueprint $table) {
                $table->dropUnique(['end_day', 'inventory_item_id']);
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('daily_bar_items', function (Blueprint $table) {
                $table->unique(['daily_bar_snapshot_id', 'inventory_item_id']);
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
            Schema::table('daily_bar_items', function (Blueprint $table) {
                $table->dropUnique(['daily_bar_snapshot_id', 'inventory_item_id']);
            });
        } catch (\Throwable $e) {
        }

        if (Schema::hasColumn('daily_bar_items', 'daily_bar_snapshot_id')) {
            Schema::table('daily_bar_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('daily_bar_snapshot_id');
            });
        }

        try {
            Schema::table('daily_bar_items', function (Blueprint $table) {
                $table->unique(['end_day', 'inventory_item_id']);
            });
        } catch (\Throwable $e) {
        }

        DB::table('daily_bar_snapshots')->delete();
    }
};
