<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();

            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('duration');
            $table->enum('duration_type', ['day', 'month', 'year']);

            $table->timestamps();

            $table->unique(
                ['package_id', 'country_id', 'currency_id', 'duration', 'duration_type'],
                'prices_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
