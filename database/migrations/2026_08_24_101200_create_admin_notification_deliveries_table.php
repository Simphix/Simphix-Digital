<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-subscription delivery outcome for an admin notification — lets
     * the sending job prune dead subscriptions (push service returns
     * 404/410) and gives per-notification delivery visibility.
     */
    public function up(): void
    {
        Schema::create('admin_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('push_subscription_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->comment('pending, sent, failed, expired');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['admin_notification_id', 'push_subscription_id'], 'notif_delivery_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_deliveries');
    }
};
