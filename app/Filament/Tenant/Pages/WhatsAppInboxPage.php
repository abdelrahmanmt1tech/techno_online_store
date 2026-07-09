<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Shared\WhatsApp\Concerns\InteractsWithWhatsAppInbox;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class WhatsAppInboxPage extends Page
{
    use ChecksWhatsAppPermissions;
    use InteractsWithWhatsAppInbox;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected static ?int $navigationSort = 41;

    protected string $view = 'filament.tenant.pages.whatsapp-inbox';

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
        return static::canWhatsAppPermission('whatsapp.view_inbox');
    }

    public function mount(): void
    {
        if ($this->conversations->isNotEmpty() && $this->selectedConversationId === null) {
            $this->selectedConversationId = $this->conversations->first()->id;
        }
    }

    public function sendTenantReply(): void
    {
        $this->sendReply('tenant');
    }

    public function sendTenantTemplateReply(): void
    {
        $this->sendTemplateReply('tenant');
    }
}
