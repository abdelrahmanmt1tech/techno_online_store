<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->json('name');

            $table->json('title')
                ->nullable();

            $table->json('description')
                ->nullable();

            $table->enum('type', [
                'commission',
                'subscription',
            ]);

            $table->decimal('price', 12, 2)
                ->default(0);

            $table->decimal('commission_per_order', 12, 2)
                ->nullable();

            $table->enum('subscription_period', ['monthly', 'yearly'])
                ->nullable();

            $table->char('currency', 3)
                ->default('SAR');

            $table->boolean('is_active')
                ->default(true);

            $table->integer('order')
                ->default(0);

            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->json('name');

            $table->boolean('is_active')
                ->default(true);

            $table->integer('order')
                ->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
