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
        Schema::create('level_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., '200 TRX', '400 TRX'
            $table->decimal('price', 15, 2); // e.g., 200, 400, 800
            $table->integer('level_number')->unique(); // 1-15
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true); // Enable/disable plans
            $table->integer('sort_order')->default(0); // For ordering plans
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('level_plans');
    }
};
