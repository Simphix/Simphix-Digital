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
        Schema::table('sim_export_settings', function (Blueprint $table) {
            $table->timestamp('last_exported_at')->nullable()->after('is_active');
            $table->timestamp('last_attempted_at')->nullable()->after('last_exported_at');
            $table->string('last_export_status', 32)->nullable()->after('last_attempted_at');
            $table->unsignedInteger('last_export_count')->nullable()->after('last_export_status');
            $table->string('last_export_recipient')->nullable()->after('last_export_count');
            $table->text('last_export_message')->nullable()->after('last_export_recipient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sim_export_settings', function (Blueprint $table) {
            $table->dropColumn([
                'last_exported_at',
                'last_attempted_at',
                'last_export_status',
                'last_export_count',
                'last_export_recipient',
                'last_export_message',
            ]);
        });
    }
};
