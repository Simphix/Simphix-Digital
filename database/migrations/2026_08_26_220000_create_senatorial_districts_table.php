<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senatorial_districts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // Required: a district must always belong to a state.
            $table->foreignId('state_id')->constrained('states')->restrictOnDelete();
            // Nullable + unique: mirrors regions.regional_manager_id — a
            // district can be unassigned, and at most one district may
            // claim a given coordinator (1:1).
            $table->foreignId('coordinator_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senatorial_districts');
    }
};
