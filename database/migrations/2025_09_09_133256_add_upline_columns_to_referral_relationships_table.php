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
            $table->bigInteger('upline1')->nullable()->after('upline_id');
            $table->bigInteger('upline2')->nullable()->after('upline1');
            $table->bigInteger('upline3')->nullable()->after('upline2');
            $table->bigInteger('upline4')->nullable()->after('upline3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_relationships', function (Blueprint $table) {
            $table->dropColumn(['upline1', 'upline2', 'upline3', 'upline4']);
        });
    }
};
