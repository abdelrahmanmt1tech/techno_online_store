<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * POS foundation tables (tenant DB). No Vue UI in this phase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_drawers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code']);
        });

        Schema::create('pos_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('cash_drawer_id')->nullable()->constrained('cash_drawers')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('receipt_prefix', 20)->default('POS');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code']);
        });

        Schema::create('pos_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type', 30)->default('cash'); // cash|card|transfer|other
            $table->boolean('is_active')->default(true);
            $table->boolean('opens_cash_drawer')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pos_settings', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number_strategy', 40)->default('per_register'); // per_register|global
            $table->string('default_currency', 8)->nullable();
            $table->boolean('require_open_session')->default(true);
            $table->boolean('allow_suspend_sales')->default(true);
            $table->boolean('allow_negative_stock')->default(false);
            $table->json('meta')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cashier_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_register_id')->constrained('pos_registers')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('open'); // open|closed
            $table->string('device_name')->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('expected_balance', 14, 2)->nullable();
            $table->decimal('actual_balance', 14, 2)->nullable();
            $table->decimal('difference', 14, 2)->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->text('difference_reason')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pos_register_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_session_id')->constrained('cashier_sessions')->cascadeOnDelete();
            $table->foreignId('cash_drawer_id')->nullable()->constrained('cash_drawers')->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('amount', 14, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cashier_session_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cashier_sessions');
        Schema::dropIfExists('pos_settings');
        Schema::dropIfExists('pos_payment_methods');
        Schema::dropIfExists('pos_registers');
        Schema::dropIfExists('cash_drawers');
    }
};
