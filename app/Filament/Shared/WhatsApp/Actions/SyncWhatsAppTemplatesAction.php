<?php

namespace App\Filament\Shared\WhatsApp\Actions;

use App\WhatsApp\Actions\SyncWhatsAppTemplatesFromMetaAction;
use App\WhatsApp\DTOs\SyncWhatsAppTemplatesResult;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class SyncWhatsAppTemplatesAction
{
    public static function make(?callable $visible = null): Action
    {
        return Action::make('syncTemplates')
            ->label(__('dashboard.whatsapp_sync_templates'))
            ->icon(Heroicon::ArrowPath)
            ->color('gray')
            ->visible($visible ?? fn (): bool => true)
            ->requiresConfirmation()
            ->modalHeading(__('dashboard.whatsapp_sync_templates'))
            ->modalDescription(__('dashboard.whatsapp_sync_templates_confirm'))
            ->action(function (SyncWhatsAppTemplatesFromMetaAction $action): void {
                try {
                    $result = $action->execute();
                    static::notifyResult($result);
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->title(__('dashboard.whatsapp_sync_templates_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function notifyResult(SyncWhatsAppTemplatesResult $result): void
    {
        if ($result->hasErrors() && $result->totalSynced() === 0) {
            Notification::make()
                ->title(__('dashboard.whatsapp_sync_templates_failed'))
                ->body(implode("\n", $result->errors))
                ->danger()
                ->send();

            return;
        }

        $notification = Notification::make()
            ->title(__('dashboard.whatsapp_sync_templates_success'))
            ->body(__('dashboard.whatsapp_sync_templates_summary', [
                'created' => $result->created,
                'updated' => $result->updated,
                'skipped' => $result->skipped,
            ]));

        if ($result->hasErrors()) {
            $notification
                ->warning()
                ->body(
                    __('dashboard.whatsapp_sync_templates_summary', [
                        'created' => $result->created,
                        'updated' => $result->updated,
                        'skipped' => $result->skipped,
                    ])."\n".implode("\n", $result->errors)
                );
        } else {
            $notification->success();
        }

        $notification->send();
    }
}
