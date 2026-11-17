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
        Schema::create('passport_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('application_type', 20)->default('new')->index(); // new, renewal
            $table->string('previous_passport_number', 50)->nullable();
            
            // Personal Details
            $table->string('nin', 20)->index();
            $table->string('surname', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth');
            $table->string('state_of_origin', 80);
            $table->string('lga', 100);
            $table->string('hometown', 120);
            $table->string('place_of_birth', 120);
            $table->string('phone', 30);
            $table->string('email', 120);
            $table->text('residential_address');
            $table->string('gender', 20); // male, female
            $table->string('occupation', 120);
            
            // Next of Kin Details
            $table->string('next_of_kin_name', 150);
            $table->string('next_of_kin_relationship', 80);
            $table->string('next_of_kin_phone', 30);
            $table->text('next_of_kin_address');
            
            // Uploaded Documents (Local Storage Paths)
            $table->string('passport_photo_path')->nullable();
            $table->string('state_origin_cert_path')->nullable();
            $table->string('birth_cert_path')->nullable();
            $table->string('nin_doc_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('previous_passport_path')->nullable();
            
            // Lifecycle & Administrative Status
            $table->string('status', 30)->default('pending')->index();
            $table->dateTime('capturing_date')->nullable();
            $table->string('capturing_center')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->dateTime('contacted_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passport_requests');
    }
};
