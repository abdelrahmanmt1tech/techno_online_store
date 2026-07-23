<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity_on_hand', 14, 4)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'inventory_item_id'], 'stock_balances_warehouse_item_unique');
        });

        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->string('transaction_type', 32);
            $table->string('status', 32)->default('draft');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('source_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->date('transaction_date');
            $table->nullableMorphs('reference');
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_transaction_id')->nullable()->constrained('stock_transactions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transaction_type', 'status']);
            $table->index(['transaction_date', 'status']);
        });

        Schema::create('stock_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transaction_id')->constrained('stock_transactions')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->restrictOnDelete();
            $table->string('source_kind', 16)->default('inventory');
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->decimal('total_cost', 14, 4)->default(0);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->boolean('affects_commerce_quantity')->default(false);
            $table->decimal('commerce_quantity_delta', 14, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transaction_id')->constrained('stock_transactions')->restrictOnDelete();
            $table->foreignId('stock_transaction_line_id')->constrained('stock_transaction_lines')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('direction', 8);
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('total_cost', 14, 4)->default(0);
            $table->dateTime('movement_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'inventory_item_id', 'movement_date'], 'stock_movements_wh_item_date_idx');
        });

        Schema::create('stock_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->nullableMorphs('source');
            $table->dateTime('received_at');
            $table->decimal('original_quantity', 14, 4);
            $table->decimal('remaining_quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('total_cost', 14, 4);
            $table->string('status', 16)->default('open');
            $table->timestamps();

            $table->index(['warehouse_id', 'inventory_item_id', 'received_at', 'id'], 'stock_cost_layers_fifo_idx');
            $table->index(['status', 'remaining_quantity']);
        });

        Schema::create('stock_layer_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_movement_id')->constrained('stock_movements')->restrictOnDelete();
            $table->foreignId('stock_cost_layer_id')->constrained('stock_cost_layers')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('total_cost', 14, 4);
            $table->timestamps();

            $table->index(['stock_cost_layer_id', 'stock_movement_id'], 'stock_layer_consumptions_layer_movement_idx');
        });

        // سجل تأثيرات صريحة على كمية المتجر — ليس مزامنة دائمة
        Schema::create('commerce_quantity_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('quantity_before');
            $table->integer('quantity_delta');
            $table->integer('quantity_after');
            $table->string('reason', 64);
            $table->string('document_type', 64)->nullable();
            $table->string('document_number')->nullable()->index();
            $table->nullableMorphs('reference');
            $table->string('idempotency_key')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_quantity_adjustments');
        Schema::dropIfExists('stock_layer_consumptions');
        Schema::dropIfExists('stock_cost_layers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_transaction_lines');
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('stock_balances');
    }
};
