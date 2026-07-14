<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');

        Schema::create('product_variations', function (Blueprint $table) {
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

            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });

        Schema::create('product_variation_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('variation_id')
                ->constrained('product_variations')
                ->cascadeOnDelete();

            $table->string('value');

            $table->string('color_code')->nullable();

            $table->integer('order')->default(0);

            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('price', 10, 2);

            $table->decimal('sale_price', 10, 2)
                ->nullable();

            $table->decimal('expense', 10, 2)
                ->nullable();

            $table->integer('quantity')
                ->default(0);

            $table->string('sku')
                ->nullable();

            $table->string('image')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });

        Schema::create('product_variant_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('option_id')
                ->constrained('product_variation_options')
                ->cascadeOnDelete();

            $table->unique([
                'variant_id',
                'option_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_options');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_variation_options');
        Schema::dropIfExists('product_variations');

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
};
