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
        Schema::table('referral_relationships', function (Blueprint $table) {
            // upline is a valid MLM term for the person above in the referral hierarchy
            $table->bigInteger('main_upline_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_relationships', function (Blueprint $table) {
            $table->dropColumn('main_upline_id');
        });
    }
};
