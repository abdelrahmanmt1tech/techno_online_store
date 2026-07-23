<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->json('print_settings_snapshot')->nullable()->after('notes');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->json('print_settings_snapshot')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('print_settings_snapshot');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('print_settings_snapshot');
        });
    }
};
