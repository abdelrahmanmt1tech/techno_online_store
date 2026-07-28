<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_theme_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();

            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');

            $table->enum('status', [
                'active',
                'expired',
                'cancelled',
            ])->default('active');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'theme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_theme_subscriptions');
    }
};
