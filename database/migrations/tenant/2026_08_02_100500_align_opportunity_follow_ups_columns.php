<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Align opportunity_follow_ups with flyaram model/UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('opportunity_follow_ups', 'parent_id')
            && ! Schema::hasColumn('opportunity_follow_ups', 'parent_follow_up_id')) {
            // Drop FK on parent_id if present, then rename.
            $fk = DB::selectOne("
                SELECT CONSTRAINT_NAME AS name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'opportunity_follow_ups'
                  AND COLUMN_NAME = 'parent_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ");
            if ($fk?->name) {
                DB::statement('ALTER TABLE `opportunity_follow_ups` DROP FOREIGN KEY `'.$fk->name.'`');
            }
            DB::statement('ALTER TABLE `opportunity_follow_ups` CHANGE `parent_id` `parent_follow_up_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `opportunity_follow_ups` ADD CONSTRAINT `ofu_parent_fk` FOREIGN KEY (`parent_follow_up_id`) REFERENCES `opportunity_follow_ups` (`id`) ON DELETE SET NULL');
        }

        Schema::table('opportunity_follow_ups', function (Blueprint $table): void {
            if (! Schema::hasColumn('opportunity_follow_ups', 'next_follow_up_at')) {
                $table->timestamp('next_follow_up_at')->nullable();
            }
            if (! Schema::hasColumn('opportunity_follow_ups', 'offer_text')) {
                $table->longText('offer_text')->nullable();
            }
            if (! Schema::hasColumn('opportunity_follow_ups', 'customer_reply')) {
                $table->longText('customer_reply')->nullable();
            }
            if (! Schema::hasColumn('opportunity_follow_ups', 'internal_notes')) {
                $table->text('internal_notes')->nullable();
            }
            if (! Schema::hasColumn('opportunity_follow_ups', 'meta')) {
                $table->json('meta')->nullable();
            }
        });

        Schema::table('opportunity_follow_ups', function (Blueprint $table): void {
            $drop = [];
            if (Schema::hasColumn('opportunity_follow_ups', 'title')) {
                $drop[] = 'title';
            }
            if (Schema::hasColumn('opportunity_follow_ups', 'description')) {
                $drop[] = 'description';
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        //
    }
};
