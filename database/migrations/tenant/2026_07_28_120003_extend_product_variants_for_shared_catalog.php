<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('sku');
            $table->decimal('weight', 14, 4)->nullable()->after('barcode');
            $table->decimal('length', 14, 4)->nullable()->after('weight');
            $table->decimal('width', 14, 4)->nullable()->after('length');
            $table->decimal('height', 14, 4)->nullable()->after('width');
            $table->json('meta')->nullable()->after('image');

            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'barcode',
                'weight',
                'length',
                'width',
                'height',
                'meta',
            ]);
        });
    }
};
