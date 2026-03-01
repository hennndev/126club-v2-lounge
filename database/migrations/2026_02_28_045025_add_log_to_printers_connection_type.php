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
        Schema::table('printers', function (Blueprint $table) {
            // Change from enum to string so new types can be added without further migrations
            $table->string('connection_type')->default('network')->change();
        });
    }

    public function down(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            $table->enum('connection_type', ['network', 'file', 'windows'])->default('network')->change();
        });
    }
};
