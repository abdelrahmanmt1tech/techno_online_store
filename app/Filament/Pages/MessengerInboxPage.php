<?php

namespace App\Filament\Pages;

use App\Filament\Shared\Messenger\Concerns\ChecksMessengerPermissions;
use App\Filament\Shared\Messenger\Concerns\InteractsWithMessengerInbox;
use App\Messenger\Services\MessengerTenantContextService;
use App\Models\Tenant;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MessengerInboxPage extends Page
{
    use ChecksMessengerPermissions;
    use InteractsWithMessengerInbox;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected static ?int $navigationSort = 51;

    protected string $view = 'filament.shared.messenger.inbox-admin';

    public ?string $selectedTenantId = null;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.messenger_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.messenger_inbox');
    }

    public static function canAccess(): bool
    {
        return static::canMessengerPermission('messenger.view_inbox', 'messenger.platform.troubleshoot');
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
        $this->replyBody = '';
        unset($this->conversations, $this->messages, $this->selectedConversation, $this->canSendFreeform, $this->canReply);

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
        $this->sendReply();
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

        $context = app(MessengerTenantContextService::class);
        $context->end();
        $context->initializeForTenant($tenant);
    }

    protected function endTenantContext(): void
    {
        app(MessengerTenantContextService::class)->end();
    }
}
