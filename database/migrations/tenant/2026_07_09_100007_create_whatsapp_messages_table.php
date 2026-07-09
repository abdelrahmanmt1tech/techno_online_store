<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->foreignId('whatsapp_number_id')->constrained('whatsapp_numbers')->cascadeOnDelete();
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('direction');
            $table->string('sender_type');
            $table->string('type');
            $table->text('body')->nullable();
            $table->json('media_metadata')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('status')->default('pending');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
            $table->string('template_name')->nullable();
            $table->string('template_language')->nullable();
            $table->json('template_variables')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
