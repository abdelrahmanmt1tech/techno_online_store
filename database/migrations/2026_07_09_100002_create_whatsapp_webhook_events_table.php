<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('meta');
            $table->string('event_type')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->string('processing_status')->default('pending');
            $table->json('payload')->nullable();
            $table->boolean('payload_redacted')->default(false);
            $table->boolean('signature_valid')->nullable();
            $table->json('diagnostic_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('phone_number_id');
            $table->index('tenant_id');
            $table->index('processing_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_events');
    }
};
