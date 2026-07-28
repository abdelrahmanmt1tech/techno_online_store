<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('pos_register_id')->nullable()->after('branch_id')->constrained('pos_registers')->nullOnDelete();
            $table->foreignId('cashier_session_id')->nullable()->after('pos_register_id')->constrained('cashier_sessions')->nullOnDelete();
            $table->boolean('is_suspended')->default(false)->after('notes');
            $table->timestamp('suspended_at')->nullable()->after('is_suspended');
            $table->timestamp('resumed_at')->nullable()->after('suspended_at');

            $table->index(['is_suspended', 'status']);
            $table->index('cashier_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pos_register_id');
            $table->dropConstrainedForeignId('cashier_session_id');
            $table->dropColumn(['is_suspended', 'suspended_at', 'resumed_at']);
        });
    }
};
