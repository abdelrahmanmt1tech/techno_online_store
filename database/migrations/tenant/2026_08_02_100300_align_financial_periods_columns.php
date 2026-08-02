<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align financial_periods with flyaram FinancialPeriod model columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_periods', function (Blueprint $table): void {
            if (Schema::hasColumn('financial_periods', 'starts_on') && ! Schema::hasColumn('financial_periods', 'start_date')) {
                $table->renameColumn('starts_on', 'start_date');
            }
            if (Schema::hasColumn('financial_periods', 'ends_on') && ! Schema::hasColumn('financial_periods', 'end_date')) {
                $table->renameColumn('ends_on', 'end_date');
            }
        });

        Schema::table('financial_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('financial_periods', 'code')) {
                $table->string('code')->nullable()->after('name');
            }
            if (! Schema::hasColumn('financial_periods', 'is_current')) {
                $table->boolean('is_current')->default(false)->after('status');
            }
            if (! Schema::hasColumn('financial_periods', 'parent_period_id')) {
                $table->foreignId('parent_period_id')->nullable()->after('is_current')->constrained('financial_periods')->nullOnDelete();
            }
            if (! Schema::hasColumn('financial_periods', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }
            if (! Schema::hasColumn('financial_periods', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('financial_periods', 'reopened_at')) {
                $table->timestamp('reopened_at')->nullable();
            }
            if (! Schema::hasColumn('financial_periods', 'reopened_by')) {
                $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('financial_periods', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        // non-destructive rollback omitted
    }
};
