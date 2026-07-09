<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_api_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_number_id')->constrained('whatsapp_numbers')->cascadeOnDelete();
            $table->foreignId('whatsapp_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            $table->string('operation');
            $table->string('recipient_phone')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('api_error_code')->nullable();
            $table->string('outcome');
            $table->string('status_label');
            $table->text('summary');
            $table->json('request_payload')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('operation');
            $table->index('outcome');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_api_requests');
    }
};
