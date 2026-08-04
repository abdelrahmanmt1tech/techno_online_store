<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropUnique('prices_unique');

            $table->dropColumn('type');
            $table->renameColumn('price', 'price_monthly');
            $table->decimal('price_yearly', 12, 2)->default(0)->after('price_monthly');
            $table->boolean('is_default')->default(false)->after('price_yearly');

            $table->unique(
                ['package_id', 'country_id', 'currency_id'],
                'prices_unique'
            );

            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropUnique('prices_unique');

            $table->renameColumn('price_monthly', 'price');
            $table->dropColumn(['price_yearly', 'is_default']);
            $table->enum('type', ['monthly', 'yearly'])->default('monthly');

            $table->unique(
                ['package_id', 'country_id', 'currency_id', 'type'],
                'prices_unique'
            );

            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
        });
    }
};
