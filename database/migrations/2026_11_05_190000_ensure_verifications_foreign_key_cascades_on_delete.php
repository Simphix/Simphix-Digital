<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            return;
        }

        // 1. Drop existing foreign key constraint if it exists with RESTRICT
        try {
            Schema::table('verifications', function (Blueprint $table) {
                $table->dropForeign('verifications_transaction_id_foreign');
            });
        } catch (\Throwable $e) {
            // Foreign key might already be dropped or named differently
        }

        // 2. Make transaction_id nullable so records can exist independently
        try {
            DB::statement('ALTER TABLE `verifications` MODIFY `transaction_id` BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Ignore if already nullable or unsupported
        }

        // 3. Re-add foreign key constraint with cascade on delete
        try {
            Schema::table('verifications', function (Blueprint $table) {
                $table->foreign('transaction_id')
                    ->references('id')
                    ->on('transactions')
                    ->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
            // Ignore if re-add fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op on rollback
    }
};
