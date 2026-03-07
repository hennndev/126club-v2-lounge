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
            // MySQL: extend the enum with the new value
            DB::statement("ALTER TABLE billings MODIFY COLUMN billing_status ENUM('draft','finalized','paid','partially_paid','force_closed') NOT NULL DEFAULT 'draft'");
        } else {
            // SQLite (used in tests): drop the CHECK constraint by changing to plain string
            Schema::table('billings', function (Blueprint $table): void {
                $table->string('billing_status')->default('draft')->change();
            });
        }

        Schema::table('billings', function (Blueprint $table): void {
            $table->string('closing_notes')->nullable()->after('notes');
        });
    }

    /**
     * Reverse placeholder.
     */
    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table): void {
            $table->dropColumn('closing_notes');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE billings MODIFY COLUMN billing_status ENUM('draft','finalized','paid','partially_paid') NOT NULL DEFAULT 'draft'");
        }
    }
};
