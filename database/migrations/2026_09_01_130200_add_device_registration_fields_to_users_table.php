<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('device_registration_enabled')->default(true)->after('suspended_by');
            $table->string('phone_verification_otp_hash', 64)->nullable()->after('device_registration_enabled');
            $table->timestamp('phone_verification_otp_expires_at')->nullable()->after('phone_verification_otp_hash');
        });

        // Existing users must not be surprised by an OTP wall on their very
        // next login the moment this ships — the feature is on by default
        // for every account created from here on, but pre-existing accounts
        // start opted out and enable it themselves from Settings.
        DB::table('users')->update(['device_registration_enabled' => false]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['device_registration_enabled', 'phone_verification_otp_hash', 'phone_verification_otp_expires_at']);
        });
    }
};
