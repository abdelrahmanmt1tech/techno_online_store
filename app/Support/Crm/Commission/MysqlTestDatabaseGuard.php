<?php

namespace App\Support\Crm\Commission;

use RuntimeException;

final class MysqlTestDatabaseGuard
{
    public static function assertSafeTestingEnvironment(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException(
                'MySQL commission test database operations are only permitted when APP_ENV=testing.',
            );
        }

        self::assertDatabaseNameIsSafe();
    }

    public static function assertDatabaseNameIsSafe(): void
    {
        $testDatabase = (string) config('database.connections.mysql_testing.database', '');

        if ($testDatabase === '') {
            throw new RuntimeException('DB_TEST_DATABASE must be configured for mysql_testing.');
        }

        $blockedNames = array_unique(array_filter([
            (string) config('database.connections.mysql.database', ''),
            (string) env('DB_DATABASE', ''),
            (string) env('DB_TEST_BLOCKED_DATABASE', ''),
        ]));

        if (in_array($testDatabase, $blockedNames, true)) {
            throw new RuntimeException(
                "DB_TEST_DATABASE [{$testDatabase}] must not match production or development database names.",
            );
        }
    }

    public static function assertMysqlTestingConnection(): void
    {
        if (config('database.default') !== 'mysql_testing') {
            throw new RuntimeException(
                'Commission MySQL tests must use the mysql_testing connection only.',
            );
        }
    }
}
