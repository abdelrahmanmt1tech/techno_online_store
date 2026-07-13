<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_onboarding_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('nonce')->unique();
            $table->string('tenant_id');
            $table->string('user_id')->nullable();
            $table->string('connection_method');
            $table->string('status');
            $table->string('waba_id')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('display_phone_number')->nullable();
            $table->string('business_id')->nullable();
            $table->string('meta_event')->nullable();
            $table->json('session_payload')->nullable();
            $table->text('access_token')->nullable();
            $table->unsignedBigInteger('tenant_whatsapp_number_id')->nullable();
            $table->text('last_error')->nullable();
            $table->text('return_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('phone_number_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_onboarding_sessions');
    }
};
