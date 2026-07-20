<?php

namespace App\Filament\Pages;

use App\Filament\Shared\Messenger\Concerns\ChecksMessengerPermissions;
use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Models\Tenant;
use App\Support\MessagingHealth\InspectTenantMessagingConnectionAction;
use App\Support\MessagingHealth\MessagingHealthStatus;
use App\Support\MessagingHealth\MessagingHealthSummaryService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class MessagingHealthDashboard extends Page
{
    use ChecksMessengerPermissions;
    use ChecksWhatsAppPermissions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Heart;

    protected static ?string $slug = 'messaging-health';

    protected static ?int $navigationSort = 35;

    protected string $view = 'filament.pages.messaging-health-dashboard';

    public int $webhookPeriodHours = 24;

    public string $filterChannel = '';

    public string $filterTenantId = '';

    public string $filterHealth = '';

    public string $filterStatus = '';

    public string $filterWebhookStatus = '';

    public string $filterConnectionMethod = '';

    public string $filterSearch = '';

    public bool $needsAttentionOnly = true;

    /** @var array<string, mixed>|null */
    public ?array $inspection = null;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.messaging_operations_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.messaging_health');
    }

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.messaging_health');
    }

    public static function canAccess(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_numbers', 'whatsapp.platform.troubleshoot')
            || static::canMessengerPermission('messenger.view_pages', 'messenger.platform.troubleshoot');
    }

    public function refreshDashboard(): void
    {
        $this->inspection = null;

        Notification::make()
            ->title(__('dashboard.messaging_health_refreshed'))
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryProperty(): array
    {
        return app(MessagingHealthSummaryService::class)->summarize($this->webhookPeriodHours);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAttentionRowsProperty(): Collection
    {
        return app(MessagingHealthSummaryService::class)->attentionRows([
            'channel' => $this->filterChannel !== '' ? $this->filterChannel : null,
            'tenant_id' => $this->filterTenantId !== '' ? $this->filterTenantId : null,
            'health' => $this->filterHealth !== '' ? $this->filterHealth : null,
            'status' => $this->filterStatus !== '' ? $this->filterStatus : null,
            'webhook_status' => $this->filterWebhookStatus !== '' ? $this->filterWebhookStatus : null,
            'connection_method' => $this->filterConnectionMethod !== '' ? $this->filterConnectionMethod : null,
            'search' => $this->filterSearch !== '' ? $this->filterSearch : null,
            'needs_attention_only' => $this->needsAttentionOnly,
        ])->take(100)->values();
    }

    /**
     * @return Collection<int, Tenant>
     */
    public function getTenantsProperty(): Collection
    {
        return Tenant::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getHealthOptionsProperty(): array
    {
        return collect(MessagingHealthStatus::cases())
            ->map(fn (MessagingHealthStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }

    public function inspectConnection(string $channel, int $registryId): void
    {
        try {
            $this->inspection = app(InspectTenantMessagingConnectionAction::class)
                ->execute($channel, $registryId);
        } catch (\Throwable $exception) {
            $this->inspection = null;

            Notification::make()
                ->title(__('dashboard.messaging_health_inspect_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function closeInspection(): void
    {
        $this->inspection = null;
    }

    public function registryUrl(string $channel): string
    {
        return $channel === 'whatsapp'
            ? url('/admin/whats-app-numbers')
            : url('/admin/messenger-pages');
    }

    public function webhookEventsUrl(string $channel): string
    {
        return $channel === 'whatsapp'
            ? url('/admin/whats-app-webhook-events')
            : url('/admin/messenger-webhook-events');
    }
}
