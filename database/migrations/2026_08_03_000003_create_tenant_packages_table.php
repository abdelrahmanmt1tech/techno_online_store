<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_packages', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id', 36);
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('package_id')->constrained()->cascadeOnDelete();

            $table->decimal('price', 12, 2)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('duration');
            $table->enum('duration_type', ['day', 'month', 'year']);

            $table->dateTime('started_at');
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('expires_at')->nullable();

            $table->enum('status', ['trial', 'active', 'expired', 'cancelled'])->default('active');

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_packages');
    }
};
