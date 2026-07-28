<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9 — POS runtime: session lifecycle, tender totals, immutable movements, receipts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->decimal('expected_cash', 14, 2)->nullable()->after('opening_balance');
            $table->decimal('expected_card', 14, 2)->nullable()->after('expected_cash');
            $table->decimal('expected_transfer', 14, 2)->nullable()->after('expected_card');
            $table->decimal('expected_other', 14, 2)->nullable()->after('expected_transfer');

            $table->decimal('actual_cash', 14, 2)->nullable()->after('expected_other');
            $table->decimal('actual_card', 14, 2)->nullable()->after('actual_cash');
            $table->decimal('actual_transfer', 14, 2)->nullable()->after('actual_card');
            $table->decimal('actual_other', 14, 2)->nullable()->after('actual_transfer');

            $table->timestamp('closing_started_at')->nullable()->after('opened_at');
            $table->timestamp('cancelled_at')->nullable()->after('closed_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable()->after('cancelled_by');
        });

        // Normalize legacy open → opened for any seeded rows.
        DB::table('cashier_sessions')->where('status', 'open')->update(['status' => 'opened']);

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->string('payment_method_type', 30)->nullable()->after('type');
            $table->string('payment_method_code', 50)->nullable()->after('payment_method_type');
            $table->string('direction', 10)->default('in')->after('amount'); // in|out
            $table->foreignId('sale_id')->nullable()->after('direction')->constrained('sales')->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->after('sale_id')->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('invoice_payment_id')->nullable()->after('sales_invoice_id')->constrained('invoice_payments')->nullOnDelete();
            $table->foreignId('reverses_movement_id')->nullable()->after('invoice_payment_id')->constrained('cash_movements')->nullOnDelete();
            $table->boolean('is_reversal')->default(false)->after('reverses_movement_id');
            $table->json('meta')->nullable()->after('notes');
        });

        Schema::create('pos_receipt_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('pos_register_id')->constrained('pos_registers')->cascadeOnDelete();
            $table->date('sequence_date');
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['branch_id', 'pos_register_id', 'sequence_date'], 'pos_receipt_seq_unique');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('document_number');
            $table->timestamp('suspended_until')->nullable()->after('suspended_at');
            $table->timestamp('suspend_cancelled_at')->nullable()->after('resumed_at');
            $table->foreignId('suspend_cancelled_by')->nullable()->after('suspend_cancelled_at')->constrained('users')->nullOnDelete();

            $table->index('receipt_number');
        });

        Schema::table('pos_settings', function (Blueprint $table) {
            $table->unsignedInteger('suspend_expires_minutes')->nullable()->after('allow_suspend_sales');
        });
    }

    public function down(): void
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropColumn('suspend_expires_minutes');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspend_cancelled_by');
            $table->dropColumn(['receipt_number', 'suspended_until', 'suspend_cancelled_at']);
        });

        Schema::dropIfExists('pos_receipt_sequences');

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
            $table->dropConstrainedForeignId('sales_invoice_id');
            $table->dropConstrainedForeignId('invoice_payment_id');
            $table->dropConstrainedForeignId('reverses_movement_id');
            $table->dropColumn([
                'payment_method_type',
                'payment_method_code',
                'direction',
                'is_reversal',
                'meta',
            ]);
        });

        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'expected_cash',
                'expected_card',
                'expected_transfer',
                'expected_other',
                'actual_cash',
                'actual_card',
                'actual_transfer',
                'actual_other',
                'closing_started_at',
                'cancelled_at',
                'cancel_reason',
            ]);
        });

        DB::table('cashier_sessions')->where('status', 'opened')->update(['status' => 'open']);
    }
};
