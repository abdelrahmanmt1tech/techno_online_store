<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Double-entry accounting core (no Payment / SafesBank / AccountStatement).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('string_value')->nullable();
            $table->timestamps();
        });

        Schema::create('account_trees', function (Blueprint $table): void {
            $table->id();
            $table->string('account_name');
            $table->string('account_code')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('account_trees')->nullOnDelete();
            $table->string('account_type')->nullable();
            $table->string('balance_side', 16)->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounts_centers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->foreignId('account_tree_id')->nullable()->constrained('account_trees')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 32)->default('open');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('financial_period_id')->nullable()->constrained('financial_periods')->nullOnDelete();
            $table->dateTime('date')->nullable();
            $table->text('comment')->nullable();
            $table->string('reference_no')->nullable();
            $table->boolean('settlement')->default(false);
            $table->boolean('status')->default(true);
            $table->string('operation_type', 32)->nullable();
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_system_generated')->default(false);
            $table->nullableMorphs('linkable');
            $table->nullableMorphs('service');
            $table->foreignId('source_operation_id')->nullable()->constrained('operations')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operation_id')->constrained('operations')->cascadeOnDelete();
            $table->foreignId('account_tree_id')->constrained('account_trees')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('debit', 15, 2)->nullable();
            $table->decimal('credit', 15, 2)->nullable();
            $table->string('entry_type', 32)->nullable();
            $table->date('day_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounts_center_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('accounts_center_id')->constrained('accounts_centers')->cascadeOnDelete();
            $table->foreignId('operation_id')->nullable()->constrained('operations')->nullOnDelete();
            $table->string('movement_type', 32)->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->nullableMorphs('linkable');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('period_closings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('financial_period_id')->constrained('financial_periods')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('period_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('from_period_id')->constrained('financial_periods')->cascadeOnDelete();
            $table->foreignId('to_period_id')->constrained('financial_periods')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();
        });

        Schema::create('account_period_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('financial_period_id')->constrained('financial_periods')->cascadeOnDelete();
            $table->foreignId('account_tree_id')->constrained('account_trees')->cascadeOnDelete();
            $table->string('balance_side', 16)->nullable();
            $table->decimal('opening_debit', 15, 2)->default(0);
            $table->decimal('opening_credit', 15, 2)->default(0);
            $table->decimal('period_debit', 15, 2)->default(0);
            $table->decimal('period_credit', 15, 2)->default(0);
            $table->decimal('closing_debit', 15, 2)->default(0);
            $table->decimal('closing_credit', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['financial_period_id', 'account_tree_id'], 'apb_period_account_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_period_balances');
        Schema::dropIfExists('period_transfers');
        Schema::dropIfExists('period_closings');
        Schema::dropIfExists('accounts_center_movements');
        Schema::dropIfExists('entries');
        Schema::dropIfExists('operations');
        Schema::dropIfExists('financial_periods');
        Schema::dropIfExists('accounts_centers');
        Schema::dropIfExists('account_trees');
        Schema::dropIfExists('tenant_settings');
    }
};
