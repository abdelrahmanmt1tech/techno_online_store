<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM foundation: extend customers + create pipeline/commission tables.
 * client_id FKs point at customers (Client alias).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('company_name')->nullable()->after('name');
            $table->string('gondc_name')->nullable()->after('company_name');
            $table->string('email')->nullable()->after('gondc_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('tax_number')->nullable()->after('phone');
            $table->string('commercial_register')->nullable()->after('tax_number');
            $table->text('address')->nullable()->after('commercial_register');
            $table->string('stage', 32)->nullable()->after('address');
            $table->foreignId('lead_source_id')->nullable()->after('stage');
            $table->foreignId('sales_rep_id')->nullable()->after('lead_source_id');
            $table->foreignId('first_followed_by')->nullable()->after('sales_rep_id');
            $table->decimal('commission_amount', 15, 2)->nullable()->after('first_followed_by');
            $table->boolean('is_provisional')->default(false)->after('commission_amount');
            $table->unsignedBigInteger('account_tree_id')->nullable()->after('is_provisional');
            $table->unsignedBigInteger('accounts_center_id')->nullable()->after('account_tree_id');
            $table->decimal('credit_limit', 15, 2)->nullable()->after('accounts_center_id');
            $table->softDeletes();
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->string('gondc_name')->nullable()->after('name');
            $table->unsignedBigInteger('account_tree_id')->nullable()->after('gondc_name');
            $table->unsignedBigInteger('accounts_center_id')->nullable()->after('account_tree_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('is_admin');
        });

        Schema::create('lead_sources', function (Blueprint $table): void {
            $table->id();
            $table->json('name')->nullable();
            $table->string('key')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreign('lead_source_id')->references('id')->on('lead_sources')->nullOnDelete();
            $table->foreign('sales_rep_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('first_followed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('opportunity_stages', function (Blueprint $table): void {
            $table->id();
            $table->json('name')->nullable();
            $table->string('action', 32)->default('none');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('follow_up_statuses', function (Blueprint $table): void {
            $table->id();
            $table->json('name')->nullable();
            $table->string('action', 32)->default('none');
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('follow_up_types', function (Blueprint $table): void {
            $table->id();
            $table->json('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->json('name')->nullable();
            $table->json('description')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('opportunity_stage_id')->constrained('opportunity_stages');
            $table->string('title');
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('agreed_amount', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('first_assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunity_stage_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('opportunity_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->constrained('opportunity_stages');
            $table->foreignId('changed_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunity_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignId('follow_up_type_id')->nullable()->constrained('follow_up_types')->nullOnDelete();
            $table->foreignId('follow_up_status_id')->nullable()->constrained('follow_up_statuses')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('opportunity_follow_ups')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->morphs('noteable');
            $table->foreignId('created_by')->constrained('users');
            $table->longText('note');
            $table->boolean('is_private')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunity_assignment_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('changed_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunity_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('commission_type', 32);
            $table->decimal('base_amount', 15, 2)->default('0.00');
            $table->decimal('commission_percentage', 5, 2)->default('0.00');
            $table->decimal('commission_amount', 15, 2)->default('0.00');
            $table->decimal('paid_amount', 15, 2)->default('0.00');
            $table->string('status', 32)->default('draft');
            $table->string('source', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_at')->nullable();
            $table->string('last_manual_edit_field', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['opportunity_id', 'user_id', 'commission_type'], 'oc_opp_user_type_uq');
            $table->index(['user_id', 'status'], 'oc_user_status_idx');
            $table->index(['branch_id', 'status'], 'oc_branch_status_idx');
            $table->index('source', 'oc_source_idx');
        });

        Schema::create('commission_payment_cycles', function (Blueprint $table): void {
            $table->id();
            $table->string('cycle_number')->unique('cpc_cycle_number_uq');
            $table->date('period_from');
            $table->date('period_to');
            $table->string('status', 32)->default('draft');
            $table->date('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'period_from', 'period_to'], 'cpc_status_period_idx');
        });

        Schema::create('commission_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opportunity_commission_id')->constrained('opportunity_commissions')->restrictOnDelete();
            $table->foreignId('commission_payment_cycle_id')->nullable()->constrained('commission_payment_cycles')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('entry_type', 16);
            $table->decimal('amount', 15, 2);
            $table->decimal('commission_amount_snapshot', 15, 2);
            $table->decimal('paid_amount_before', 15, 2);
            $table->decimal('paid_amount_after', 15, 2);
            $table->decimal('remaining_amount_after', 15, 2);
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->timestamp('executed_at');
            $table->foreignId('executed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reverses_payment_id')->nullable()->constrained('commission_payments')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique('reverses_payment_id', 'cp_reverses_payment_uq');
        });

        Schema::create('commission_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event');
            $table->json('payload')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id'], 'cal_auditable_idx');
        });

        Schema::create('opportunity_commission_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opportunity_commission_id')->constrained('opportunity_commissions')->cascadeOnDelete();
            $table->string('direction', 16);
            $table->decimal('amount', 15, 2);
            $table->string('status', 32)->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('commission_payment_cycle_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commission_payment_cycle_id')->constrained('commission_payment_cycles')->cascadeOnDelete();
            $table->foreignId('opportunity_commission_id')->constrained('opportunity_commissions')->restrictOnDelete();
            $table->decimal('allocated_amount', 15, 2)->default('0.00');
            $table->timestamps();
            $table->unique(['commission_payment_cycle_id', 'opportunity_commission_id'], 'cpc_alloc_uq');
        });

        Schema::create('commission_payment_cycle_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->unique('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_payment_cycle_sequences');
        Schema::dropIfExists('commission_payment_cycle_allocations');
        Schema::dropIfExists('opportunity_commission_adjustments');
        Schema::dropIfExists('commission_audit_logs');
        Schema::dropIfExists('commission_payments');
        Schema::dropIfExists('commission_payment_cycles');
        Schema::dropIfExists('opportunity_commissions');
        Schema::dropIfExists('opportunity_assignment_logs');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('opportunity_follow_ups');
        Schema::dropIfExists('opportunity_stage_logs');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('follow_up_types');
        Schema::dropIfExists('follow_up_statuses');
        Schema::dropIfExists('opportunity_stages');

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropForeign(['lead_source_id']);
            $table->dropForeign(['sales_rep_id']);
            $table->dropForeign(['first_followed_by']);
        });

        Schema::dropIfExists('lead_sources');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('commission_percentage');
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropColumn(['gondc_name', 'account_tree_id', 'accounts_center_id']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn([
                'company_name', 'gondc_name', 'email', 'phone', 'tax_number', 'commercial_register',
                'address', 'stage', 'lead_source_id', 'sales_rep_id', 'first_followed_by',
                'commission_amount', 'is_provisional', 'account_tree_id', 'accounts_center_id', 'credit_limit',
                'deleted_at',
            ]);
        });
    }
};
