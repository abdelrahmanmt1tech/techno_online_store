<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('messenger_conversations')->cascadeOnDelete();
            $table->foreignId('messenger_page_id')->constrained('messenger_pages')->cascadeOnDelete();
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('direction');
            $table->string('sender_type');
            $table->string('type')->default('text');
            $table->text('body')->nullable();
            $table->json('media_metadata')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('status')->default('pending');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
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
        Schema::dropIfExists('messenger_messages');
    }
};
