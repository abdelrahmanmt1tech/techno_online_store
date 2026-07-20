<?php

namespace App\Support\MessagingHealth;

use App\Messenger\Enums\MessengerConnectionMethod;
use App\Messenger\Enums\MessengerPageStatus;
use App\Models\MessengerPageRegistry;
use App\Models\MessengerWebhookEvent;
use App\Models\WhatsAppNumberRegistry;
use App\Models\WhatsAppWebhookEvent;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Central-DB-only messaging health aggregates and attention rows.
 * Never initializes tenancy. Never reads tokens.
 */
class MessagingHealthSummaryService
{
    public function __construct(
        protected WhatsAppRegistryHealthEvaluator $whatsAppEvaluator,
        protected MessengerRegistryHealthEvaluator $messengerEvaluator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summarize(int $webhookPeriodHours = 24): array
    {
        $whatsapp = $this->whatsAppSummary();
        $messenger = $this->messengerSummary();
        $webhooks = $this->webhookAggregates($webhookPeriodHours);
        $tenants = $this->tenantIntegrationSummary();

        return [
            'whatsapp' => $whatsapp,
            'messenger' => $messenger,
            'tenants' => $tenants,
            'webhooks' => $webhooks,
            'attention_count' => $this->attentionCount(),
            'webhook_period_hours' => $webhookPeriodHours,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function whatsAppSummary(): array
    {
        $base = WhatsAppNumberRegistry::query();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->where('status', WhatsAppConnectionStatus::Active)->count(),
            'reconnect_required' => (clone $base)->where('status', WhatsAppConnectionStatus::ReconnectRequired)->count(),
            'failed' => (clone $base)->where('status', WhatsAppConnectionStatus::Failed)->count(),
            'disabled' => (clone $base)->where(function ($q) {
                $q->where('is_active', false)->orWhere('status', WhatsAppConnectionStatus::Disabled);
            })->count(),
            'webhook_subscribed' => (clone $base)->where('webhook_status', 'subscribed')->count(),
            'pending_onboarding' => (clone $base)->whereIn('onboarding_status', [
                WhatsAppOnboardingStatus::InProgress,
                WhatsAppOnboardingStatus::AwaitingPhoneSelection,
                WhatsAppOnboardingStatus::SubscribingWebhooks,
            ])->count(),
            'method_manual' => (clone $base)->where('connection_method', WhatsAppConnectionMethod::ManualApiOnly)->count(),
            'method_api_only' => (clone $base)->where('connection_method', WhatsAppConnectionMethod::EmbeddedSignupApiOnly)->count(),
            'method_coexistence' => (clone $base)->where('connection_method', WhatsAppConnectionMethod::EmbeddedSignupCoexistence)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function messengerSummary(): array
    {
        $base = MessengerPageRegistry::query();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->where('status', MessengerPageStatus::Active)->count(),
            'reconnect_required' => (clone $base)->where('status', MessengerPageStatus::ReconnectRequired)->count(),
            'failed' => (clone $base)->where('status', MessengerPageStatus::Failed)->count(),
            'disabled' => (clone $base)->where(function ($q) {
                $q->where('is_active', false)->orWhere('status', MessengerPageStatus::Disabled);
            })->count(),
            'webhook_subscribed' => (clone $base)->where('webhook_status', 'subscribed')->count(),
            'method_manual' => (clone $base)->where('connection_method', MessengerConnectionMethod::Manual)->count(),
            'method_facebook_login' => (clone $base)->where('connection_method', MessengerConnectionMethod::FacebookLogin)->count(),
        ];
    }

    /**
     * @return array{tenants_with_messaging: int, whatsapp_only: int, messenger_only: int, both: int}
     */
    public function tenantIntegrationSummary(): array
    {
        $wa = WhatsAppNumberRegistry::query()->distinct()->pluck('tenant_id');
        $ms = MessengerPageRegistry::query()->distinct()->pluck('tenant_id');

        $waSet = $wa->map(fn ($id) => (string) $id)->unique()->values();
        $msSet = $ms->map(fn ($id) => (string) $id)->unique()->values();
        $both = $waSet->intersect($msSet)->values();

        return [
            'tenants_with_messaging' => $waSet->merge($msSet)->unique()->count(),
            'whatsapp_only' => $waSet->diff($msSet)->count(),
            'messenger_only' => $msSet->diff($waSet)->count(),
            'both' => $both->count(),
        ];
    }

    /**
     * @return array{whatsapp: array<string, mixed>, messenger: array<string, mixed>}
     */
    public function webhookAggregates(int $hours): array
    {
        $since = now()->subHours(max(1, $hours));

        return [
            'whatsapp' => $this->channelWebhookAggregates(WhatsAppWebhookEvent::class, $since),
            'messenger' => $this->channelWebhookAggregates(MessengerWebhookEvent::class, $since),
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @return array{processed: int, failed: int, unresolved: int, rejected: int, latest_processed_at: ?string}
     */
    protected function channelWebhookAggregates(string $modelClass, CarbonInterface $since): array
    {
        $counts = $modelClass::query()
            ->where('created_at', '>=', $since)
            ->select('processing_status', DB::raw('count(*) as aggregate'))
            ->groupBy('processing_status')
            ->pluck('aggregate', 'processing_status')
            ->mapWithKeys(fn ($count, $status) => [
                (is_object($status) && property_exists($status, 'value') ? $status->value : (string) $status) => (int) $count,
            ]);

        $latest = $modelClass::query()
            ->where('processing_status', 'processed')
            ->where('created_at', '>=', $since)
            ->max('processed_at');

        return [
            'processed' => (int) ($counts['processed'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
            'unresolved' => (int) ($counts['unresolved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'latest_processed_at' => $latest ? Carbon::parse($latest)->toIso8601String() : null,
        ];
    }

    public function attentionCount(): int
    {
        return $this->attentionRows(needsAttentionOnly: true)->count();
    }

    /**
     * Build unified attention rows from central registries (no tenant DB).
     *
     * @param  array{
     *   channel?: ?string,
     *   tenant_id?: ?string,
     *   health?: ?string,
     *   connection_method?: ?string,
     *   status?: ?string,
     *   webhook_status?: ?string,
     *   search?: ?string,
     *   needs_attention_only?: bool
     * }  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function attentionRows(array $filters = [], bool $needsAttentionOnly = false): Collection
    {
        $needsAttentionOnly = $filters['needs_attention_only'] ?? $needsAttentionOnly;

        $whatsapp = WhatsAppNumberRegistry::query()
            ->with(['tenant:id,name,email'])
            ->when(filled($filters['tenant_id'] ?? null), fn ($q) => $q->where('tenant_id', $filters['tenant_id']))
            ->when(filled($filters['connection_method'] ?? null) && ($filters['channel'] ?? null) !== 'messenger',
                fn ($q) => $q->where('connection_method', $filters['connection_method']))
            ->when(filled($filters['status'] ?? null), fn ($q) => $q->where('status', $filters['status']))
            ->when(filled($filters['webhook_status'] ?? null), fn ($q) => $q->where('webhook_status', $filters['webhook_status']))
            ->when(filled($filters['search'] ?? null), function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('display_phone_number', 'like', $term)
                        ->orWhere('phone_number_id', 'like', $term)
                        ->orWhere('business_name', 'like', $term)
                        ->orWhereHas('tenant', fn ($tq) => $tq->where('name', 'like', $term));
                });
            })
            ->get()
            ->map(fn (WhatsAppNumberRegistry $row) => $this->mapWhatsAppRow($row));

        $messenger = MessengerPageRegistry::query()
            ->with(['tenant:id,name,email'])
            ->when(filled($filters['tenant_id'] ?? null), fn ($q) => $q->where('tenant_id', $filters['tenant_id']))
            ->when(filled($filters['connection_method'] ?? null) && ($filters['channel'] ?? null) !== 'whatsapp',
                fn ($q) => $q->where('connection_method', $filters['connection_method']))
            ->when(filled($filters['status'] ?? null), fn ($q) => $q->where('status', $filters['status']))
            ->when(filled($filters['webhook_status'] ?? null), fn ($q) => $q->where('webhook_status', $filters['webhook_status']))
            ->when(filled($filters['search'] ?? null), function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('page_name', 'like', $term)
                        ->orWhere('page_id', 'like', $term)
                        ->orWhereHas('tenant', fn ($tq) => $tq->where('name', 'like', $term));
                });
            })
            ->get()
            ->map(fn (MessengerPageRegistry $row) => $this->mapMessengerRow($row));

        $rows = $whatsapp->concat($messenger);

        if (($filters['channel'] ?? null) === 'whatsapp') {
            $rows = $rows->where('channel', 'whatsapp')->values();
        } elseif (($filters['channel'] ?? null) === 'messenger') {
            $rows = $rows->where('channel', 'messenger')->values();
        }

        if (filled($filters['health'] ?? null)) {
            $rows = $rows->where('health', $filters['health'])->values();
        }

        if ($needsAttentionOnly) {
            $rows = $rows->filter(fn (array $row) => $row['needs_attention'])->values();
        }

        return $rows->sortByDesc(fn (array $row) => $row['updated_at'] ?? '')->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapWhatsAppRow(WhatsAppNumberRegistry $row): array
    {
        $health = $this->whatsAppEvaluator->evaluate($row);

        return [
            'key' => 'whatsapp:'.$row->id,
            'channel' => 'whatsapp',
            'registry_id' => $row->id,
            'tenant_id' => (string) $row->tenant_id,
            'tenant_name' => $row->tenant?->name ?? (string) $row->tenant_id,
            'asset_name' => $row->display_phone_number ?: ($row->business_name ?: $row->phone_number_id),
            'asset_id' => $this->maskId((string) $row->phone_number_id),
            'asset_id_raw' => (string) $row->phone_number_id,
            'connection_method' => $row->connection_method?->value,
            'status' => $row->status?->value,
            'webhook_status' => $row->webhook_status,
            'onboarding_status' => $row->onboarding_status?->value,
            'health' => $health->value,
            'health_label' => $health->label(),
            'health_color' => $health->color(),
            'needs_attention' => $health->needsAttention(),
            'last_inbound_at' => optional($row->last_inbound_at)?->toDateTimeString(),
            'last_outbound_at' => optional($row->last_outbound_at)?->toDateTimeString(),
            'last_health_check_at' => optional($row->last_health_check_at)?->toDateTimeString(),
            'reconnect_required_at' => $row->status === WhatsAppConnectionStatus::ReconnectRequired
                ? optional($row->updated_at)?->toDateTimeString()
                : null,
            'safe_error_summary' => null,
            'updated_at' => optional($row->updated_at)?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapMessengerRow(MessengerPageRegistry $row): array
    {
        $health = $this->messengerEvaluator->evaluate($row);

        return [
            'key' => 'messenger:'.$row->id,
            'channel' => 'messenger',
            'registry_id' => $row->id,
            'tenant_id' => (string) $row->tenant_id,
            'tenant_name' => $row->tenant?->name ?? (string) $row->tenant_id,
            'asset_name' => $row->page_name ?: $row->page_id,
            'asset_id' => $this->maskId((string) $row->page_id),
            'asset_id_raw' => (string) $row->page_id,
            'connection_method' => $row->connection_method?->value,
            'status' => $row->status?->value,
            'webhook_status' => $row->webhook_status,
            'onboarding_status' => null,
            'health' => $health->value,
            'health_label' => $health->label(),
            'health_color' => $health->color(),
            'needs_attention' => $health->needsAttention(),
            'last_inbound_at' => optional($row->last_inbound_at)?->toDateTimeString(),
            'last_outbound_at' => optional($row->last_outbound_at)?->toDateTimeString(),
            'last_health_check_at' => optional($row->last_health_check_at)?->toDateTimeString(),
            'reconnect_required_at' => $row->status === MessengerPageStatus::ReconnectRequired
                ? optional($row->updated_at)?->toDateTimeString()
                : null,
            'safe_error_summary' => null,
            'updated_at' => optional($row->updated_at)?->toDateTimeString(),
        ];
    }

    public function maskId(string $id): string
    {
        $id = trim($id);

        if ($id === '') {
            return '—';
        }

        if (strlen($id) <= 6) {
            return str_repeat('*', max(strlen($id) - 2, 0)).substr($id, -2);
        }

        return substr($id, 0, 3).str_repeat('*', max(strlen($id) - 6, 3)).substr($id, -3);
    }

    /**
     * Recent webhook counts for one asset (central only).
     *
     * @return array{processed: int, failed: int, unresolved: int, rejected: int}
     */
    public function recentWebhookCountsForAsset(string $channel, string $assetId, int $hours = 24): array
    {
        $since = now()->subHours(max(1, $hours));

        if ($channel === 'whatsapp') {
            $query = WhatsAppWebhookEvent::query()
                ->where('phone_number_id', $assetId)
                ->where('created_at', '>=', $since);
        } else {
            $query = MessengerWebhookEvent::query()
                ->where('page_id', $assetId)
                ->where('created_at', '>=', $since);
        }

        $counts = (clone $query)
            ->select('processing_status', DB::raw('count(*) as aggregate'))
            ->groupBy('processing_status')
            ->pluck('aggregate', 'processing_status')
            ->mapWithKeys(fn ($count, $status) => [
                (is_object($status) && property_exists($status, 'value') ? $status->value : (string) $status) => (int) $count,
            ]);

        return [
            'processed' => (int) ($counts['processed'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
            'unresolved' => (int) ($counts['unresolved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }
}
