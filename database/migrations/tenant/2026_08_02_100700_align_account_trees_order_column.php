<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Align account_trees with Filament TreeView (`order`) + flyaram columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('account_trees', 'sort') && ! Schema::hasColumn('account_trees', 'order')) {
            DB::statement('ALTER TABLE `account_trees` CHANGE `sort` `order` INT UNSIGNED NOT NULL DEFAULT 99');
        }

        if (! Schema::hasColumn('account_trees', 'order')) {
            Schema::table('account_trees', function (Blueprint $table): void {
                $table->unsignedInteger('order')->default(99);
            });
        }

        if (! Schema::hasColumn('account_trees', 'level')) {
            Schema::table('account_trees', function (Blueprint $table): void {
                $table->unsignedInteger('level')->default(1)->after('account_type');
            });
        }

        if (! Schema::hasColumn('account_trees', 'branch_id')) {
            Schema::table('account_trees', function (Blueprint $table): void {
                $table->foreignId('branch_id')->nullable()->after('level')->constrained('branches')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('account_trees', 'income_general_statement')) {
            Schema::table('account_trees', function (Blueprint $table): void {
                $table->string('income_general_statement', 16)->default('none')->after('branch_id');
            });
        }

        if (! Schema::hasColumn('account_trees', 'main_acc_status')) {
            Schema::table('account_trees', function (Blueprint $table): void {
                $table->string('main_acc_status', 16)->nullable()->after('order');
            });
        }
    }

    public function down(): void
    {
        //
    }
};
