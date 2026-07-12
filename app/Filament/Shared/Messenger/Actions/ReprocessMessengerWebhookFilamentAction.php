<?php

namespace App\Filament\Shared\Messenger\Actions;

use App\Messenger\Actions\ReprocessMessengerWebhookAction;
use App\Models\MessengerWebhookEvent;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ReprocessMessengerWebhookFilamentAction
{
    public static function make(): Action
    {
        return Action::make('reprocessWebhook')
            ->label(__('dashboard.messenger_reprocess_webhook'))
            ->icon(Heroicon::ArrowPath)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('dashboard.messenger_reprocess_webhook'))
            ->modalDescription(__('dashboard.messenger_reprocess_webhook_confirm'))
            ->visible(fn (MessengerWebhookEvent $record): bool => $record->canReprocess())
            ->action(function (MessengerWebhookEvent $record): void {
                try {
                    app(ReprocessMessengerWebhookAction::class)->execute($record);

                    Notification::make()
                        ->title(__('dashboard.messenger_reprocess_webhook_success'))
                        ->success()
                        ->send();
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->title(__('dashboard.messenger_reprocess_webhook_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
