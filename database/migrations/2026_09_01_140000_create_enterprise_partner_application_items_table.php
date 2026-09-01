<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_partner_application_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('enterprise_partner_applications')->cascadeOnDelete();
            $table->string('sim_category');
            $table->string('provider');
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('unit_price_snapshot', 15, 2);
            $table->decimal('line_total_snapshot', 15, 2);
            $table->timestamps();

            $table->unique(['application_id', 'sim_category', 'provider'], 'ep_app_items_app_cat_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_partner_application_items');
    }
};
