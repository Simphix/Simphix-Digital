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
        Schema::table('moniepoint_pos_requests', function (Blueprint $table) {
            $table->string('passport_photo')->nullable()->after('email');
            $table->string('bvn', 20)->nullable()->after('passport_photo');
            $table->string('nin', 20)->nullable()->after('bvn');
            $table->text('shop_address')->nullable()->after('nin');
            $table->string('state')->nullable()->after('shop_address');
            $table->string('lga')->nullable()->after('state');
            $table->text('home_address')->nullable()->after('lga');

            // Next of Kin Details
            $table->string('next_of_kin_name')->nullable()->after('home_address');
            $table->string('next_of_kin_phone', 30)->nullable()->after('next_of_kin_name');
            $table->text('next_of_kin_address')->nullable()->after('next_of_kin_phone');

            // Referral Details
            $table->string('referral_name')->nullable()->after('next_of_kin_address');
            $table->string('referral_phone', 30)->nullable()->after('referral_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('moniepoint_pos_requests', function (Blueprint $table) {
            $table->dropColumn([
                'passport_photo',
                'bvn',
                'nin',
                'shop_address',
                'state',
                'lga',
                'home_address',
                'next_of_kin_name',
                'next_of_kin_phone',
                'next_of_kin_address',
                'referral_name',
                'referral_phone',
            ]);
        });
    }
};
