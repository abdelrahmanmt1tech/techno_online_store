<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE messenger_contacts MODIFY profile_picture_url TEXT NULL');

            return;
        }

        // SQLite (and similar) already store strings with TEXT affinity / no strict 255 limit.
        // No structural change required for test environments.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        // Rollback to VARCHAR(2048) — safer than 255 for FB CDN URLs.
        // If any stored URL exceeds 2048 characters, MySQL may reject this ALTER (preferred over silent truncation).
        DB::statement('ALTER TABLE messenger_contacts MODIFY profile_picture_url VARCHAR(2048) NULL');
    }
};
