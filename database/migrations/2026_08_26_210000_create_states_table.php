<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // Nullable: a state can be created before being assigned to a
            // region. restrictOnDelete: DB-level backstop blocking deletion
            // of a Region that still has States — paired with an app-level
            // precheck in the controller for a friendly error message.
            $table->foreignId('region_id')->nullable()->constrained('regions')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
