<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE table_sessions MODIFY COLUMN status ENUM('pending','active','completed','force_closed') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE table_reservations MODIFY COLUMN status ENUM('pending','confirmed','checked_in','completed','cancelled','rejected','force_closed') NOT NULL DEFAULT 'pending'");
        } else {
            // SQLite: drop CHECK constraint by converting to plain string
            Schema::table('table_sessions', function (Blueprint $table): void {
                $table->string('status')->default('pending')->change();
            });
            Schema::table('table_reservations', function (Blueprint $table): void {
                $table->string('status')->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE table_sessions MODIFY COLUMN status ENUM('pending','active','completed') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE table_reservations MODIFY COLUMN status ENUM('pending','confirmed','checked_in','completed','cancelled','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
