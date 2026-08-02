<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align accounts_center_movements + suppliers soft deletes for accounting/CRM UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_center_movements', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounts_center_movements', 'movement_date')) {
                $table->date('movement_date')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('accounts_center_movements', 'notes')) {
                $table->text('notes')->nullable()->after('movement_type');
            }
        });

        if (! Schema::hasColumn('suppliers', 'deleted_at')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        //
    }
};
