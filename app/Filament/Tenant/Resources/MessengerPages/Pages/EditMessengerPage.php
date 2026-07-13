<?php

namespace App\Filament\Tenant\Resources\MessengerPages\Pages;

use App\Filament\Tenant\Pages\ConnectMessengerPage;
use App\Filament\Tenant\Resources\MessengerPages\MessengerPageResource;
use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Onboarding\ConnectSelectedMessengerPagesAction;
use App\Models\Tenant;
use App\Models\Tenant\MessengerPage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditMessengerPage extends EditRecord
{
    protected static string $resource = MessengerPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retryWebhookSubscription')
                ->label(__('dashboard.messenger_retry_webhook_subscription'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => (Auth::user()?->can('messenger.manage_pages') || config('app.bypass_permissions'))
                    && filled($this->record->page_access_token)
                    && in_array($this->record->webhook_status, ['failed', 'pending', null], true))
                ->action(function (): void {
                    $tenant = tenant();

                    if (! $tenant instanceof Tenant) {
                        Notification::make()
                            ->title(__('dashboard.messenger_onboarding_tenant_required'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $page = app(ConnectSelectedMessengerPagesAction::class)
                        ->retrySubscription($tenant, $this->record);

                    $this->record->refresh();

                    if ($page->webhook_status === 'subscribed') {
                        Notification::make()
                            ->title(__('dashboard.messenger_retry_webhook_subscription_success'))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('dashboard.messenger_retry_webhook_subscription_failed'))
                            ->body($page->last_error_message)
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('reconnectFacebookLogin')
                ->label(__('dashboard.messenger_reconnect_facebook_login'))
                ->url(fn (): string => ConnectMessengerPage::getUrl())
                ->visible(fn (): bool => (Auth::user()?->can('messenger.manage_pages') || config('app.bypass_permissions'))
                    && ($this->record->status === MessengerPageStatus::ReconnectRequired
                        || filled($this->record->reconnect_required_at))),
            DeleteAction::make()
                ->visible(fn () => Auth::user()?->can('messenger.manage_pages') || config('app.bypass_permissions')),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['page_access_token'] ?? null)) {
            unset($data['page_access_token']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->is_default) {
            return;
        }

        MessengerPage::query()
            ->whereKeyNot($this->record->getKey())
            ->update(['is_default' => false]);
    }
}
