<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_operating_hours', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('day_of_week')->unsigned()->unique()->comment('0=Sunday, 1=Monday, ..., 6=Saturday');
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_operating_hours');
    }
};
