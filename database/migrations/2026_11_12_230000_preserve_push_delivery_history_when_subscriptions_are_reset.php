<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_notification_deliveries', function (Blueprint $table) {
            $table->dropForeign(['push_subscription_id']);
            $table->unsignedBigInteger('push_subscription_id')->nullable()->change();
            $table->foreign('push_subscription_id')
                ->references('id')
                ->on('push_subscriptions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('admin_notification_deliveries')->whereNull('push_subscription_id')->delete();

        Schema::table('admin_notification_deliveries', function (Blueprint $table) {
            $table->dropForeign(['push_subscription_id']);
            $table->unsignedBigInteger('push_subscription_id')->nullable(false)->change();
            $table->foreign('push_subscription_id')
                ->references('id')
                ->on('push_subscriptions')
                ->cascadeOnDelete();
        });
    }
};
