<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->enum('type', ['monthly', 'yearly'])->default('monthly')->after('price');

            $table->dropForeign(['package_id']);
            $table->dropUnique('prices_unique');

            $table->dropColumn(['duration', 'duration_type']);

            $table->unique(
                ['package_id', 'country_id', 'currency_id', 'type'],
                'prices_unique'
            );

            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('trials_duration')->default(14)->change();
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropUnique('prices_unique');

            $table->dropColumn('type');

            $table->unsignedInteger('duration')->default(1);
            $table->enum('duration_type', ['day', 'month', 'year'])->default('month');

            $table->unique(
                ['package_id', 'country_id', 'currency_id', 'duration', 'duration_type'],
                'prices_unique'
            );

            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('trials_duration')->default(0)->change();
        });
    }
};
