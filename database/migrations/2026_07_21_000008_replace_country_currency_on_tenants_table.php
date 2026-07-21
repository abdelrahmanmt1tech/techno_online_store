<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('phone')->constrained('countries')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->after('country_id')->constrained('currencies')->nullOnDelete();
            $table->dropColumn(['country_name', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('country_name')->nullable()->after('phone');
            $table->string('currency_code')->nullable()->after('country_name');
            $table->dropForeign(['country_id']);
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['country_id', 'currency_id']);
        });
    }
};
