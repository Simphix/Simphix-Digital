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
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->string('referral_name')->nullable()->after('purpose');
            $table->string('referral_phone', 30)->nullable()->after('referral_name');
        });

        Schema::table('travel_requests', function (Blueprint $table) {
            $table->string('referral_name')->nullable()->after('notes');
            $table->string('referral_phone', 30)->nullable()->after('referral_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropColumn(['referral_name', 'referral_phone']);
        });

        Schema::table('travel_requests', function (Blueprint $table) {
            $table->dropColumn(['referral_name', 'referral_phone']);
        });
    }
};
