<?php

namespace App\Filament\Shared\WhatsApp\Actions;

use App\Models\WhatsAppWebhookEvent;
use App\WhatsApp\Actions\ReprocessWhatsAppWebhookAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ReprocessWhatsAppWebhookFilamentAction
{
    public static function make(): Action
    {
        return Action::make('reprocessWebhook')
            ->label(__('dashboard.whatsapp_reprocess_webhook'))
            ->icon(Heroicon::ArrowPath)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('dashboard.whatsapp_reprocess_webhook'))
            ->modalDescription(__('dashboard.whatsapp_reprocess_webhook_confirm'))
            ->visible(fn (WhatsAppWebhookEvent $record): bool => $record->canReprocess())
            ->action(function (WhatsAppWebhookEvent $record): void {
                try {
                    app(ReprocessWhatsAppWebhookAction::class)->execute($record);

                    Notification::make()
                        ->title(__('dashboard.whatsapp_reprocess_webhook_success'))
                        ->success()
                        ->send();
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->title(__('dashboard.whatsapp_reprocess_webhook_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
