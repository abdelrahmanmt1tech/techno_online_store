<?php

namespace App\Filament\Tenant\Resources\PurchaseReturns\Pages;

use App\Actions\Erp\PostPurchaseReturnAction;
use App\Filament\Tenant\Resources\PurchaseReturns\PurchaseReturnResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewPurchaseReturn extends ViewRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label(__('erp.actions.post'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => ($this->record->status?->value ?? $this->record->status) === 'draft')
                ->action(function () {
                    try {
                        app(PostPurchaseReturnAction::class)->execute($this->record);
                        Notification::make()->title(__('erp.notifications.posted'))->success()->send();
                        $this->refreshFormData(['status', 'posted_at', 'posted_by', 'stock_transaction_id']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(collect($e->errors())->flatten()->first() ?? __('erp.notifications.error'))
                            ->danger()
                            ->send();
                    }
                }),
            EditAction::make()->visible(fn () => ($this->record->status?->value ?? $this->record->status) === 'draft'),
        ];
    }
}
