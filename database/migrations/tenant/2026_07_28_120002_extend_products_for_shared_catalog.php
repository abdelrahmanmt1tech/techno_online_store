<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend existing products table — single source of truth for Store / ERP / POS.
 * Keeps legacy `type` (physical|digital) for storefront compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('id')->constrained('brands')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('brand_id')->constrained('units_of_measure')->nullOnDelete();

            // Shared catalog type (does not replace physical|digital `type` column).
            $table->string('catalog_type', 40)->default('inventory_item')->after('type');

            $table->string('status', 30)->default('active')->after('is_active');
            $table->string('visibility', 30)->default('visible')->after('status');

            $table->string('barcode')->nullable()->after('sku');
            $table->boolean('allow_backorders')->default(false)->after('disable_orders_for_no_stock');
            $table->decimal('low_stock_alert', 14, 4)->nullable()->after('allow_backorders');

            $table->string('tax_class', 50)->nullable()->after('low_stock_alert');
            $table->decimal('weight', 14, 4)->nullable()->after('tax_class');
            $table->decimal('length', 14, 4)->nullable()->after('weight');
            $table->decimal('width', 14, 4)->nullable()->after('length');
            $table->decimal('height', 14, 4)->nullable()->after('width');
            $table->string('dimension_unit', 10)->nullable()->after('height');

            $table->text('notes')->nullable()->after('description');
            $table->json('meta')->nullable()->after('notes');

            $table->index('catalog_type');
            $table->index('status');
            $table->index('visibility');
            $table->index('barcode');
        });

        // Backfill catalog_type from legacy type.
        DB::table('products')->where('type', 'digital')->update(['catalog_type' => 'digital']);
        DB::table('products')->where('type', 'physical')->update(['catalog_type' => 'inventory_item']);
        DB::table('products')->where('is_active', false)->update(['status' => 'archived']);
        DB::table('products')->where('is_active', true)->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn([
                'catalog_type',
                'status',
                'visibility',
                'barcode',
                'allow_backorders',
                'low_stock_alert',
                'tax_class',
                'weight',
                'length',
                'width',
                'height',
                'dimension_unit',
                'notes',
                'meta',
            ]);
        });
    }
};
