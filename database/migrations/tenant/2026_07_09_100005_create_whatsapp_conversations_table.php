<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_number_id')->constrained('whatsapp_numbers')->cascadeOnDelete();
            $table->string('customer_phone');
            $table->string('customer_name')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');
            $table->string('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('last_outbound_message_at')->nullable();
            $table->timestamp('customer_service_window_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['whatsapp_number_id', 'customer_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
