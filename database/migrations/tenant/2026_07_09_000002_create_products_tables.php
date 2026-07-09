<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('sku')->nullable();

            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('expense', 12, 2)->nullable();

            $table->integer('order')->default(0);

            $table->longText('description')->nullable();

            $table->integer('quantity')->default(0);

            $table->boolean('track_stock')->default(false);
            $table->boolean('disable_orders_for_no_stock')->default(false);

            $table->enum('type', ['physical', 'digital'])->default('physical');

            $table->string('link_if_type_digital')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unique(['product_id', 'category_id']);
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->morphs('mediable');

            $table->string('file');

            $table->string('type')->nullable();

            $table->timestamps();
        });

        Schema::create('product_codes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('code');

            $table->boolean('is_used')
                ->default(false);

            $table->timestamp('used_at')
                ->nullable();

            // $table->foreignId('order_item_id')
            //     ->nullable()
            //     ->constrained()
            //     ->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->enum('type', [
                'color',
                'button',
                'user_text',
                'user_image',
                'image',
                'dropdown',
            ]);

            $table->string('color_code')->nullable();

            $table->string('image')->nullable();

            $table->boolean('is_available')->default(true);

            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_attribute_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('attribute_value');

            $table->string('image')->nullable();

            $table->string('sku')->nullable();

            $table->decimal('price', 12, 2)->default(0);

            $table->decimal('sale_price', 12, 2)->nullable();

            $table->decimal('expense', 12, 2)->nullable();

            $table->integer('quantity')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_codes');
        Schema::dropIfExists('media');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
    }
};
