<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 64);
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('prefix', 16);
            $table->unsignedInteger('padding')->default(6);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['document_type', 'branch_id'], 'document_sequences_type_branch_unique');
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'user_id']);
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('warehouse_type', 32)->default('regular');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'warehouse_type']);
        });

        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('symbol', 16)->nullable();
            $table->boolean('allows_decimal')->default(false);
            $table->unsignedTinyInteger('precision')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('item_type', 32)->default('finished_good');
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->string('costing_method', 16)->default('fifo');
            $table->boolean('track_stock')->default(true);
            $table->decimal('default_purchase_cost', 14, 4)->nullable();
            $table->decimal('default_sale_price', 14, 2)->nullable();
            $table->decimal('minimum_stock', 14, 4)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_type', 'is_active']);
        });

        // رابط اختياري واحد بين صنف ERP ومصدر متجر (منتج أو متغير)
        Schema::create('inventory_item_commerce_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->timestamps();

            $table->unique(['source_type', 'source_id'], 'inventory_item_commerce_source_unique');
            $table->unique('inventory_item_id', 'inventory_item_commerce_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_commerce_links');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('document_sequences');
    }
};
