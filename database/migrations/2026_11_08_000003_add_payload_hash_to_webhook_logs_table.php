<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SHA-256 of the raw webhook body. A provider that retries an unanswered
     * delivery sends the same bytes again, so this lets the receiver recognise
     * a repeat and reuse its log row instead of recording a new one each time.
     */
    public function up(): void
    {
        if (Schema::hasColumn('webhook_logs', 'payload_hash')) {
            return;
        }

        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->string('payload_hash', 64)->nullable()->after('payload');
            $table->index(['provider', 'payload_hash']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('webhook_logs', 'payload_hash')) {
            return;
        }

        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropIndex(['provider', 'payload_hash']);
            $table->dropColumn('payload_hash');
        });
    }
};
