<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique();
            $table->string('token')->unique();

            $table->foreignId('cart_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'returned',
            ])->default('pending');

            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('customer_address');

            $table->foreignId('governorate_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('governorate_name');
            $table->decimal('shipping_cost', 12, 2)->default(0);

            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('coupon_code')->nullable();
            $table->decimal('discount', 12, 2)->default(0);

            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
