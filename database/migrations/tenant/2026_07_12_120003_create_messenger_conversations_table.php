<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('messenger_page_id')->constrained('messenger_pages')->cascadeOnDelete();
            $table->string('sender_psid');
            $table->string('customer_name')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('messenger_contacts')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');
            $table->string('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('last_outbound_message_at')->nullable();
            $table->timestamp('customer_service_window_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['messenger_page_id', 'sender_psid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_conversations');
    }
};
