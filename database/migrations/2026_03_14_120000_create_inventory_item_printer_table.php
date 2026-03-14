<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_item_printer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('printer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['inventory_item_id', 'printer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_printer');
    }
};
