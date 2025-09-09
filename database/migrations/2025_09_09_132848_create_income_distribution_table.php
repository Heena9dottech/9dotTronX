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
        Schema::create('income_distribution', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('level_plan_id')->nullable()->constrained('level_plans')->onDelete('set null');
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('level'); // upline1, upline2, upline3, upline4, sponsor, admin
            $table->decimal('percentage', 5, 2); // 5.00, 10.00, etc.
            $table->string('from_address')->nullable();
            $table->string('to_address')->nullable();
            $table->string('hash')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->decimal('amount', 15, 2);
            $table->decimal('level_plan_price', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'level']);
            $table->index(['recipient_id', 'level']);
            $table->index('level_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_distribution');
    }
};
