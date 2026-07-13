<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Shared\Messenger\Concerns\ChecksMessengerPermissions;
use App\Filament\Tenant\Resources\MessengerPages\MessengerPageResource;
use App\Messenger\Onboarding\MessengerOnboardingStateService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class ConnectMessengerPage extends Page
{
    use ChecksMessengerPermissions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Link;

    protected static ?int $navigationSort = 49;

    protected string $view = 'filament.tenant.pages.connect-messenger';

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.messenger_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.messenger_connect');
    }

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.messenger_connect');
    }

    public static function canAccess(): bool
    {
        return static::canMessengerPermission('messenger.manage_pages');
    }

    public function isFacebookLoginConfigured(): bool
    {
        return app(MessengerOnboardingStateService::class)->isConfigured();
    }

    public function chooseManual(): void
    {
        $this->redirect(MessengerPageResource::getUrl('create'));
    }

    public function chooseFacebookLogin(): void
    {
        if (! $this->isFacebookLoginConfigured()) {
            Notification::make()
                ->title(__('dashboard.messenger_onboarding_config_required_title'))
                ->body(__('dashboard.messenger_onboarding_config_required_body'))
                ->warning()
                ->send();

            return;
        }

        $tenant = tenant();

        if ($tenant === null) {
            Notification::make()
                ->title(__('dashboard.messenger_onboarding_tenant_required'))
                ->danger()
                ->send();

            return;
        }

        $returnUrl = url(MessengerPageResource::getUrl('index', panel: 'tenant'));

        $token = app(MessengerOnboardingStateService::class)->issue(
            tenantId: (string) $tenant->getTenantKey(),
            returnUrl: $returnUrl,
            userId: Auth::guard('tenant')->id(),
        );

        $url = app(MessengerOnboardingStateService::class)->centralUrl('start', $token);

        $this->redirect($url);
    }
}
