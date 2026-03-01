<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('discount_percentage')->default(0);
            $table->unsignedBigInteger('minimum_spent')->default(0);
            $table->boolean('is_first_tier')->default(false);
            $table->string('color')->default('slate');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiers');
    }
};
