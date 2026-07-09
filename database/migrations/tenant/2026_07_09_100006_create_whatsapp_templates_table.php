<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_number_id')->nullable()->constrained('whatsapp_numbers')->nullOnDelete();
            $table->string('whatsapp_business_account_id');
            $table->string('provider_template_id')->nullable();
            $table->string('name');
            $table->string('language');
            $table->string('category');
            $table->string('status')->default('unknown');
            $table->json('components')->nullable();
            $table->json('variables_schema')->nullable();
            $table->json('raw_payload')->nullable();
            $table->boolean('is_disabled_locally')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['name', 'language', 'whatsapp_business_account_id'], 'whatsapp_templates_name_language_waba_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
