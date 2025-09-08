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
        Schema::create('user_slots', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('level_plans_id')->nullable();
            $table->bigInteger('referral_relationship_id')->nullable();
            // $table->json('tree_member_ids')->nullable(); // Store tree structure as JSON

            // $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // $table->foreignId('level_plans_id')->constrained('level_plans')->onDelete('cascade');
            // $table->foreignId('referral_relationship_id')->constrained('referral_relationships')->onDelete('cascade');
            $table->timestamps();

            // // Indexes for better performance
            // $table->index(['user_id', 'level_plans_id']);
            // $table->index('referral_relationship_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_slots');
    }
};
