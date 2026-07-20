<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_integration_reset_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('scope');
            $table->string('status');
            $table->timestamp('previewed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('tenants_total')->default(0);
            $table->unsignedInteger('tenants_succeeded')->default(0);
            $table->unsignedInteger('tenants_failed')->default(0);
            $table->unsignedBigInteger('central_rows_deleted')->default(0);
            $table->unsignedBigInteger('tenant_rows_deleted')->default(0);
            $table->json('summary')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_integration_reset_runs');
    }
};
