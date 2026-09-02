<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprise_partner_applications', function (Blueprint $table) {
            $table->timestamp('fulfilled_at')->nullable()->after('paid_at');
            $table->foreignId('fulfilled_by')->nullable()->after('fulfilled_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enterprise_partner_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fulfilled_by');
            $table->dropColumn('fulfilled_at');
        });
    }
};
