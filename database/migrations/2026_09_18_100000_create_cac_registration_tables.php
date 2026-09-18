<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cac_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40)->index();
            $table->string('status', 40)->default('submitted')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('transaction_ref')->nullable();
            $table->json('form_data');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cac_registration_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cac_registration_id')->constrained()->cascadeOnDelete();
            $table->string('slot', 60);
            $table->string('label')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('uploaded_by', 10)->default('user'); // user | admin
            $table->boolean('is_deliverable')->default(false);
            $table->timestamps();
        });

        Schema::create('cac_registration_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cac_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // status | note | upload
            $table->string('status_from', 40)->nullable();
            $table->string('status_to', 40)->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('visible_to_user')->default(true);
            $table->timestamps();
        });

        // Admin-configurable pricing lives on the existing Services/ServiceFields
        // infrastructure (/admin/services): one 'cac' service, one field per category.
        $now = now();
        $serviceId = DB::table('services')->where('name', 'cac')->value('id')
            ?? DB::table('services')->insertGetId([
                'name' => 'cac',
                'description' => 'CAC Business Registration',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $fields = [
            ['Business Name', 'cac_business_name', 25000, 'Business Name registration'],
            ['Private Company Limited by Shares', 'cac_private_limited', 60000, 'Private Company Limited by Shares (LTD) registration'],
            ['Incorporated Trustee', 'cac_incorporated_trustee', 120000, 'Incorporated Trustees registration'],
            ['NGO', 'cac_ngo', 120000, 'NGO registration'],
        ];
        foreach ($fields as [$name, $code, $price, $desc]) {
            if (!DB::table('service_fields')->where('field_code', $code)->exists()) {
                DB::table('service_fields')->insert([
                    'service_id' => $serviceId,
                    'field_name' => $name,
                    'field_code' => $code,
                    'description' => $desc,
                    'base_price' => $price,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cac_registration_events');
        Schema::dropIfExists('cac_registration_documents');
        Schema::dropIfExists('cac_registrations');
    }
};
