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
        Schema::create('daily_auth_codes', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->index();
            $table->string('code', 4);          // auto-generated random code
            $table->string('override_code', 4)->nullable(); // manual override
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_auth_codes');
    }
};
