<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_id')->unique();
            $table->string('page_name')->nullable();
            $table->text('page_access_token');
            $table->string('token_source')->default('manual');
            $table->string('connection_method')->default('manual');
            $table->string('status')->default('active');
            $table->string('webhook_status')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('last_error_message')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('reconnect_required_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_pages');
    }
};
