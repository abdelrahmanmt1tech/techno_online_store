<?php

namespace App\Support\MetaReset;

use App\Models\Admin;
use App\Models\MetaIntegrationResetRun;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Preview and execute Meta integration resets across central + tenant DBs.
 *
 * Not globally atomic across databases. One central transaction; one transaction per tenant.
 */
class MetaIntegrationResetService
{
    public const CONFIRMATION_PHRASE = 'RESET META INTEGRATIONS';

    public function __construct(
        protected MetaIntegrationResetRegistry $registry,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('meta.integration_reset_enabled', false);
    }

    public function confirmationPhrase(): string
    {
        return (string) config('meta.integration_reset_confirmation_phrase', self::CONFIRMATION_PHRASE);
    }

    public function permissionKey(): string
    {
        return (string) config('meta.integration_reset_permission', 'meta.integrations.reset');
    }

    public function previewTtlMinutes(): int
    {
        return max(1, (int) config('meta.integration_reset_preview_ttl_minutes', 10));
    }

    public function assertFeatureEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Meta Integration Reset is disabled. Set META_INTEGRATION_RESET_ENABLED=true.');
        }
    }

    public function assertAuthorized(?Admin $admin = null): void
    {
        $admin ??= Auth::guard('admin')->user();

        if (! $admin instanceof Admin) {
            throw new RuntimeException('Only an authenticated central admin may use Meta Integration Reset.');
        }

        if (config('app.bypass_permissions')) {
            return;
        }

        if (! $admin->can($this->permissionKey())) {
            throw new RuntimeException('You do not have permission to reset Meta integrations.');
        }
    }

    public function assertCentralContext(): void
    {
        if (tenancy()->initialized) {
            throw new RuntimeException('Meta Integration Reset must run in central context only.');
        }
    }

    /**
     * Read-only preview. Never mutates data.
     *
     * @return array<string, mixed>
     */
    public function preview(string $scope, ?Admin $admin = null): array
    {
        $this->assertFeatureEnabled();
        $this->assertAuthorized($admin);
        $this->assertCentralContext();
        $this->registry->assertValidScope($scope);

        $previewedAt = now();
        $central = $this->countCentralTables($scope);
        $tenant = $this->countTenantTables($scope);

        $token = (string) Str::uuid();
        $payload = [
            'token' => $token,
            'scope' => $scope,
            'previewed_at' => $previewedAt->toIso8601String(),
            'expires_at' => $previewedAt->copy()->addMinutes($this->previewTtlMinutes())->toIso8601String(),
            'central' => $central,
            'tenants' => $tenant,
            'preserved' => $this->registry->preservedExamples(),
            'external_note' => 'Local platform records only. Does not delete or disconnect assets inside Meta.',
        ];

        Cache::put($this->previewCacheKey($token), [
            'scope' => $scope,
            'previewed_at' => $previewedAt->timestamp,
            'central_rows' => $central['total_rows'],
            'tenant_rows' => $tenant['total_rows'],
            'tenants_total' => $tenant['tenants_total'],
            'tenants_inspected' => $tenant['tenants_inspected'],
            'tenants_failed' => $tenant['tenants_failed'],
        ], now()->addMinutes($this->previewTtlMinutes()));

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(
        string $scope,
        string $previewToken,
        string $confirmationPhrase,
        ?Admin $admin = null,
    ): array {
        $this->assertFeatureEnabled();
        $admin ??= Auth::guard('admin')->user();
        $this->assertAuthorized($admin);
        $this->assertCentralContext();
        $this->registry->assertValidScope($scope);

        if (trim($confirmationPhrase) !== $this->confirmationPhrase()) {
            throw new RuntimeException('Confirmation phrase does not match.');
        }

        $cached = Cache::get($this->previewCacheKey($previewToken));

        if (! is_array($cached) || ($cached['scope'] ?? null) !== $scope) {
            throw new RuntimeException('A valid fresh preview for this scope is required.');
        }

        $previewedAt = (int) ($cached['previewed_at'] ?? 0);
        $ttlSeconds = $this->previewTtlMinutes() * 60;

        if ($previewedAt < now()->subSeconds($ttlSeconds)->timestamp) {
            throw new RuntimeException('Preview has expired. Run Preview Reset again.');
        }

        $lockSeconds = max(60, (int) config('meta.integration_reset_lock_seconds', 300));
        $lock = Cache::lock('meta-integration-reset', $lockSeconds);

        if (! $lock->get()) {
            throw new RuntimeException('Another Meta Integration Reset is already running.');
        }

        $run = MetaIntegrationResetRun::query()->create([
            'requested_by' => $admin?->id,
            'scope' => $scope,
            'status' => MetaIntegrationResetRun::STATUS_RUNNING,
            'previewed_at' => now()->setTimestamp($previewedAt),
            'started_at' => now(),
            'tenants_total' => (int) ($cached['tenants_total'] ?? 0),
            'summary' => [
                'preview_token_present' => true,
                'scope' => $scope,
            ],
            'errors' => [],
        ]);

        $errors = [];
        $centralDeleted = 0;
        $tenantDeleted = 0;
        $tenantsSucceeded = 0;
        $tenantsFailed = 0;
        $skipped = [];
        $centralReport = [];
        $tenantReport = [];

        try {
            // Fail closed if any required table is missing before mutating.
            $this->assertRequiredTablesExist($scope);

            [$centralDeleted, $centralReport, $centralSkipped] = $this->deleteCentralTables($scope);
            $skipped = array_merge($skipped, $centralSkipped);

            [$tenantDeleted, $tenantsSucceeded, $tenantsFailed, $tenantReport, $tenantErrors, $tenantSkipped] = $this->deleteTenantTables($scope);
            $errors = array_merge($errors, $tenantErrors);
            $skipped = array_merge($skipped, $tenantSkipped);

            $status = $tenantsFailed > 0
                ? MetaIntegrationResetRun::STATUS_PARTIALLY_FAILED
                : MetaIntegrationResetRun::STATUS_COMPLETED;

            $safeSummary = [
                'scope' => $scope,
                'status' => $status,
                'central_tables' => $centralReport,
                'central_rows_deleted' => $centralDeleted,
                'tenant_tables' => $tenantReport,
                'tenant_rows_deleted' => $tenantDeleted,
                'tenants_succeeded' => $tenantsSucceeded,
                'tenants_failed' => $tenantsFailed,
                'skipped_optional_tables' => $skipped,
                'external_meta_modified' => false,
                'graph_http_calls' => 0,
            ];

            $this->assertSummaryIsSafe($safeSummary);

            $run->update([
                'status' => $status,
                'completed_at' => now(),
                'tenants_total' => $tenantsSucceeded + $tenantsFailed,
                'tenants_succeeded' => $tenantsSucceeded,
                'tenants_failed' => $tenantsFailed,
                'central_rows_deleted' => $centralDeleted,
                'tenant_rows_deleted' => $tenantDeleted,
                'summary' => $safeSummary,
                'errors' => $errors,
            ]);

            Cache::forget($this->previewCacheKey($previewToken));

            return array_merge($safeSummary, [
                'run_id' => $run->id,
                'errors' => $errors,
                'started_at' => optional($run->started_at)?->toIso8601String(),
                'completed_at' => optional($run->completed_at)?->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            $errors[] = [
                'stage' => 'execution',
                'message' => $this->safeErrorMessage($e->getMessage()),
            ];

            $run->update([
                'status' => MetaIntegrationResetRun::STATUS_FAILED,
                'completed_at' => now(),
                'tenants_succeeded' => $tenantsSucceeded,
                'tenants_failed' => $tenantsFailed,
                'central_rows_deleted' => $centralDeleted,
                'tenant_rows_deleted' => $tenantDeleted,
                'summary' => [
                    'scope' => $scope,
                    'status' => MetaIntegrationResetRun::STATUS_FAILED,
                    'central_rows_deleted' => $centralDeleted,
                    'tenant_rows_deleted' => $tenantDeleted,
                    'external_meta_modified' => false,
                ],
                'errors' => $errors,
            ]);

            throw $e;
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }

            optional($lock)->release();
        }
    }

    /**
     * @return array{tables: list<array<string, mixed>>, total_rows: int, skipped: list<array<string, mixed>>}
     */
    protected function countCentralTables(string $scope): array
    {
        $tables = [];
        $skipped = [];
        $total = 0;

        foreach ($this->registry->forScopeAndDatabase($scope, MetaIntegrationResetRegistry::DB_CENTRAL) as $def) {
            if (! Schema::connection(config('database.default'))->hasTable($def->table)) {
                if ($def->optional) {
                    $skipped[] = $def->toArray() + ['reason_skipped' => 'table_missing'];

                    continue;
                }

                throw new RuntimeException("Required central Meta table missing: {$def->table}");
            }

            $count = (int) DB::table($def->table)->count();
            $total += $count;
            $tables[] = $def->toArray() + [
                'row_count' => $count,
                'deletion_order' => $def->priority,
            ];
        }

        return [
            'tables' => $tables,
            'total_rows' => $total,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function countTenantTables(string $scope): array
    {
        $defs = $this->registry->forScopeAndDatabase($scope, MetaIntegrationResetRegistry::DB_TENANT);
        $aggregates = [];

        foreach ($defs as $def) {
            $aggregates[$def->table] = $def->toArray() + [
                'row_count' => 0,
                'tenants_with_rows' => 0,
                'deletion_order' => $def->priority,
            ];
        }

        $tenants = Tenant::query()->orderBy('id')->get();
        $inspected = 0;
        $failed = 0;
        $errors = [];
        $perTenant = [];
        $totalRows = 0;
        $skipped = [];

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $tenantRows = [];
                $tenantTotal = 0;

                foreach ($defs as $def) {
                    if (! Schema::hasTable($def->table)) {
                        if ($def->optional) {
                            $skipped[] = [
                                'tenant_id' => (string) $tenant->getTenantKey(),
                                'table' => $def->table,
                                'reason_skipped' => 'table_missing',
                            ];

                            continue;
                        }

                        throw new RuntimeException("Required tenant Meta table missing: {$def->table}");
                    }

                    $count = (int) DB::table($def->table)->count();
                    $tenantRows[$def->table] = $count;
                    $tenantTotal += $count;
                    $aggregates[$def->table]['row_count'] += $count;

                    if ($count > 0) {
                        $aggregates[$def->table]['tenants_with_rows']++;
                    }
                }

                $totalRows += $tenantTotal;
                $inspected++;
                $perTenant[] = [
                    'tenant_id' => (string) $tenant->getTenantKey(),
                    'tenant_name' => $tenant->name,
                    'total_rows' => $tenantTotal,
                    'tables' => $tenantRows,
                ];
            } catch (Throwable $e) {
                $failed++;
                $errors[] = [
                    'tenant_id' => (string) $tenant->getTenantKey(),
                    'message' => $this->safeErrorMessage($e->getMessage()),
                ];
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        return [
            'tenants_total' => $tenants->count(),
            'tenants_inspected' => $inspected,
            'tenants_failed' => $failed,
            'total_rows' => $totalRows,
            'tables' => array_values($aggregates),
            'per_tenant' => $perTenant,
            'errors' => $errors,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{0: int, 1: list<array<string, mixed>>, 2: list<array<string, mixed>>}
     */
    protected function deleteCentralTables(string $scope): array
    {
        $report = [];
        $skipped = [];
        $deleted = 0;

        DB::connection(config('database.default'))->transaction(function () use ($scope, &$report, &$skipped, &$deleted) {
            foreach ($this->registry->forScopeAndDatabase($scope, MetaIntegrationResetRegistry::DB_CENTRAL) as $def) {
                if (! Schema::hasTable($def->table)) {
                    if ($def->optional) {
                        $skipped[] = $def->toArray() + ['reason_skipped' => 'table_missing'];

                        continue;
                    }

                    throw new RuntimeException("Required central Meta table missing: {$def->table}");
                }

                $count = (int) DB::table($def->table)->count();
                DB::table($def->table)->delete();
                $deleted += $count;
                $report[] = [
                    'table' => $def->table,
                    'channel' => $def->channel,
                    'rows_deleted' => $count,
                    'credentials_table' => $def->mayContainCredentials,
                ];
            }
        });

        return [$deleted, $report, $skipped];
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: list<array<string, mixed>>, 4: list<array<string, mixed>>, 5: list<array<string, mixed>>}
     */
    protected function deleteTenantTables(string $scope): array
    {
        $defs = $this->registry->forScopeAndDatabase($scope, MetaIntegrationResetRegistry::DB_TENANT);
        $tenants = Tenant::query()->orderBy('id')->get();
        $deleted = 0;
        $succeeded = 0;
        $failed = 0;
        $report = [];
        $errors = [];
        $skipped = [];

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getTenantKey();

            try {
                tenancy()->initialize($tenant);

                $tenantDeleted = 0;
                $tenantTables = [];

                DB::transaction(function () use ($defs, &$tenantDeleted, &$tenantTables, &$skipped, $tenantId) {
                    foreach ($defs as $def) {
                        if (! Schema::hasTable($def->table)) {
                            if ($def->optional) {
                                $skipped[] = [
                                    'tenant_id' => $tenantId,
                                    'table' => $def->table,
                                    'reason_skipped' => 'table_missing',
                                ];

                                continue;
                            }

                            throw new RuntimeException("Required tenant Meta table missing: {$def->table}");
                        }

                        $count = (int) DB::table($def->table)->count();
                        DB::table($def->table)->delete();
                        $tenantDeleted += $count;
                        $tenantTables[] = [
                            'table' => $def->table,
                            'rows_deleted' => $count,
                            'credentials_table' => $def->mayContainCredentials,
                        ];
                    }
                });

                $deleted += $tenantDeleted;
                $succeeded++;
                $report[] = [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant->name,
                    'rows_deleted' => $tenantDeleted,
                    'tables' => $tenantTables,
                    'status' => 'ok',
                ];
            } catch (Throwable $e) {
                $failed++;
                $errors[] = [
                    'tenant_id' => $tenantId,
                    'message' => $this->safeErrorMessage($e->getMessage()),
                ];
                $report[] = [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant->name,
                    'status' => 'failed',
                    'rows_deleted' => 0,
                ];
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        return [$deleted, $succeeded, $failed, $report, $errors, $skipped];
    }

    protected function assertRequiredTablesExist(string $scope): void
    {
        foreach ($this->registry->forScopeAndDatabase($scope, MetaIntegrationResetRegistry::DB_CENTRAL) as $def) {
            if (! $def->optional && ! Schema::hasTable($def->table)) {
                throw new RuntimeException("Required central Meta table missing: {$def->table}");
            }
        }
    }

    protected function previewCacheKey(string $token): string
    {
        return 'meta-integration-reset-preview:'.$token;
    }

    protected function safeErrorMessage(string $message): string
    {
        $patterns = [
            '/access[_-]?token/i',
            '/page_access_token/i',
            '/user_access_token/i',
            '/oauth/i',
            '/Bearer\s+\S+/i',
            '/EAA[A-Za-z0-9]+/',
        ];

        $safe = $message;

        foreach ($patterns as $pattern) {
            $safe = preg_replace($pattern, '[redacted]', $safe) ?? $safe;
        }

        return Str::limit($safe, 500);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    protected function assertSummaryIsSafe(array $summary): void
    {
        $encoded = json_encode($summary);

        if ($encoded === false) {
            return;
        }

        if (preg_match('/access_token|page_access_token|user_access_token|Bearer\s/i', $encoded)) {
            throw new RuntimeException('Reset summary contained sensitive token material and was blocked.');
        }
    }
}
