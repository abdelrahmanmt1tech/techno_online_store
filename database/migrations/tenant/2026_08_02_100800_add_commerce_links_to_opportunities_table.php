<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            if (! Schema::hasColumn('opportunities', 'sale_id')) {
                $table->foreignId('sale_id')->nullable()->after('campaign_id')->constrained('sales')->nullOnDelete();
            }
            if (! Schema::hasColumn('opportunities', 'sales_invoice_id')) {
                $table->foreignId('sales_invoice_id')->nullable()->after('sale_id')->constrained('sales_invoices')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            if (Schema::hasColumn('opportunities', 'sales_invoice_id')) {
                $table->dropConstrainedForeignId('sales_invoice_id');
            }
            if (Schema::hasColumn('opportunities', 'sale_id')) {
                $table->dropConstrainedForeignId('sale_id');
            }
        });
    }
};
