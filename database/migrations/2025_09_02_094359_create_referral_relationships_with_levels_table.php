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
        // First drop the existing table if it exists
        Schema::dropIfExists('referral_relationships');
        
        // Create the new table with all level columns
        Schema::create('referral_relationships', function (Blueprint $table) {
            $table->id();
            
            // User information
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('user_username')->nullable();
            
            // Sponsor information
            $table->foreignId('sponsor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('sponsor_username')->nullable();
            
            // Upline information (parent in tree)
            $table->foreignId('upline_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('upline_username')->nullable();
            
            // Position in tree
            $table->enum('position', ['L','R'])->nullable()->default(null);
            
            // Tree owner information
            $table->foreignId('tree_owner_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('tree_owner_username')->nullable();
            $table->integer('tree_round')->default(1);
            $table->boolean('is_spillover_slot')->default(false);
            
            // Level plan information
            $table->integer('level_number')->default(1);
            $table->decimal('slot_price', 15, 2)->nullable();
            $table->foreignId('level_id')->nullable()->constrained('level_plans')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_relationships');
    }
};
