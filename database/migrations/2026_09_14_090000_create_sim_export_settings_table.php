<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single global settings row (see SimExportSettings::current()) for the
     * nightly "email every activated SIM number to an admin-chosen inbox"
     * job — configurable from Admin > SIMs Operations, run by the
     * `sims:export-activated` console command.
     */
    public function up(): void
    {
        Schema::create('sim_export_settings', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_export_settings');
    }
};
