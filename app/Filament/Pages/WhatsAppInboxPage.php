<?php

namespace App\Filament\Pages;

use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Shared\WhatsApp\Concerns\InteractsWithWhatsAppInbox;
use App\Models\Tenant;
use App\WhatsApp\Services\WhatsAppTenantContextService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class WhatsAppInboxPage extends Page
{
    use ChecksWhatsAppPermissions;
    use InteractsWithWhatsAppInbox;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected static ?int $navigationSort = 41;

    protected string $view = 'filament.shared.whatsapp.inbox-admin';

    public ?string $selectedTenantId = null;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.whatsapp_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.whatsapp_inbox');
    }

    public static function canAccess(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_inbox', 'whatsapp.platform.view_all_conversations');
    }

    public function mount(): void
    {
        if (filled($this->selectedTenantId)) {
            $this->initializeTenant();
        }
    }

    public function hydrate(): void
    {
        if (filled($this->selectedTenantId)) {
            $this->initializeTenant();
        }
    }

    public function dehydrate(): void
    {
        $this->endTenantContext();
    }

    public function updatedSelectedTenantId(): void
    {
        $this->selectedConversationId = null;
        unset($this->conversations, $this->messages, $this->selectedConversation);

        if (blank($this->selectedTenantId)) {
            $this->endTenantContext();

            return;
        }

        $this->initializeTenant();

        if ($this->conversations->isNotEmpty()) {
            $this->selectedConversationId = $this->conversations->first()->id;
        }
    }

    public function sendAdminReply(): void
    {
        $this->sendReply('admin');
    }

    public function sendAdminTemplateReply(): void
    {
        $this->sendTemplateReply('admin');
    }

    public function getTenantsProperty()
    {
        return Tenant::query()->orderBy('name')->get();
    }

    protected function initializeTenant(): void
    {
        if (blank($this->selectedTenantId)) {
            $this->endTenantContext();

            return;
        }

        $tenant = Tenant::query()->find($this->selectedTenantId);

        if ($tenant === null) {
            $this->endTenantContext();

            return;
        }

        $context = app(WhatsAppTenantContextService::class);
        $context->end();
        $context->initializeForTenant($tenant);
    }

    protected function endTenantContext(): void
    {
        app(WhatsAppTenantContextService::class)->end();
    }
}
