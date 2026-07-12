<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Tenant\Resources\WhatsAppNumbers\WhatsAppNumberResource;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ConnectWhatsAppPage extends Page
{
    use ChecksWhatsAppPermissions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Link;

    protected static ?int $navigationSort = 39;

    protected string $view = 'filament.tenant.pages.connect-whatsapp';

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.whatsapp_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.whatsapp_connect');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('dashboard.whatsapp_connect');
    }

    public static function canAccess(): bool
    {
        return static::canWhatsAppPermission('whatsapp.manage_numbers');
    }

    public function chooseManual(): void
    {
        $this->redirect(WhatsAppNumberResource::getUrl('create'));
    }

    public function chooseApiOnly(): void
    {
        $tenant = tenant();

        if ($tenant === null) {
            Notification::make()
                ->title(__('dashboard.whatsapp_onboarding_tenant_required'))
                ->danger()
                ->send();

            return;
        }

        $returnUrl = url(WhatsAppNumberResource::getUrl('index', panel: 'tenant'));

        $token = app(WhatsAppOnboardingStateService::class)->issue(
            tenantId: (string) $tenant->getTenantKey(),
            connectionMethod: WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            returnUrl: $returnUrl,
            userId: Auth::guard('tenant')->id(),
        );

        $url = app(WhatsAppOnboardingStateService::class)->centralUrl('start', $token);

        $this->redirect($url);
    }

    public function chooseCoexistence(): void
    {
        Notification::make()
            ->title(__('dashboard.whatsapp_onboarding_coexistence_coming_soon'))
            ->body(__('dashboard.whatsapp_onboarding_coexistence_coming_soon_body'))
            ->warning()
            ->send();
    }
}
