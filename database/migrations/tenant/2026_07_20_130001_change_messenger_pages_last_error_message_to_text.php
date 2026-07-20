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
            DB::statement('ALTER TABLE messenger_pages MODIFY last_error_message TEXT NULL');
        }

        // SQLite affinity already allows long strings in tests.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE messenger_pages MODIFY last_error_message VARCHAR(255) NULL');
    }
};
