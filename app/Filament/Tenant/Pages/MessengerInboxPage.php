<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Shared\Messenger\Concerns\ChecksMessengerPermissions;
use App\Filament\Shared\Messenger\Concerns\InteractsWithMessengerInbox;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MessengerInboxPage extends Page
{
    use ChecksMessengerPermissions;
    use InteractsWithMessengerInbox;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected static ?int $navigationSort = 51;

    protected string $view = 'filament.tenant.pages.messenger-inbox';

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
        return static::canMessengerPermission('messenger.view_inbox');
    }

    public function mount(?int $conversation = null): void
    {
        if ($conversation !== null) {
            $this->selectedConversationId = $conversation;

            return;
        }

        if ($this->conversations->isNotEmpty() && $this->selectedConversationId === null) {
            $this->selectedConversationId = $this->conversations->first()->id;
        }
    }

    public function sendTenantReply(): void
    {
        $this->sendReply();
    }
}
