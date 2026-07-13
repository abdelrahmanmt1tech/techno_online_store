<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_onboarding_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('nonce')->unique();
            $table->string('tenant_id');
            $table->string('user_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('user_access_token')->nullable();
            // Encrypted cast stores ciphertext as a string (not JSON object).
            $table->longText('pages_payload')->nullable();
            $table->json('selected_page_ids')->nullable();
            $table->json('connected_page_ids')->nullable();
            $table->text('last_error')->nullable();
            $table->string('return_url');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_onboarding_sessions');
    }
};
