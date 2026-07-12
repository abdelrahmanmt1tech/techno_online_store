<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('meta');
            $table->string('event_type')->nullable();
            $table->text('summary')->nullable();
            $table->json('interpretation')->nullable();
            $table->string('page_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->string('processing_status')->default('pending');
            $table->json('payload')->nullable();
            $table->json('original_payload')->nullable();
            $table->boolean('payload_redacted')->default(false);
            $table->boolean('signature_valid')->nullable();
            $table->json('diagnostic_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('page_id');
            $table->index('tenant_id');
            $table->index('processing_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_webhook_events');
    }
};
