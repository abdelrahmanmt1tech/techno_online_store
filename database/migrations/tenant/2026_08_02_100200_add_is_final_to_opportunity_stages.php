<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunity_stages', function (Blueprint $table): void {
            if (! Schema::hasColumn('opportunity_stages', 'is_final')) {
                $table->boolean('is_final')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_stages', function (Blueprint $table): void {
            if (Schema::hasColumn('opportunity_stages', 'is_final')) {
                $table->dropColumn('is_final');
            }
        });
    }
};
