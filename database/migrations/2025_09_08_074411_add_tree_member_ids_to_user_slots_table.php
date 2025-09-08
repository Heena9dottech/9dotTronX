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
        Schema::table('user_slots', function (Blueprint $table) {
            $table->json('tree_member_ids')->nullable()->after('referral_relationship_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_slots', function (Blueprint $table) {
            $table->dropColumn('tree_member_ids');
        });
    }
};
