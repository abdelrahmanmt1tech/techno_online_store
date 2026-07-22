<?php

namespace App\Filament\Tenant\Resources\GoodsReceipts\Pages;

use App\Actions\Erp\PostGoodsReceiptAction;
use App\Filament\Tenant\Resources\GoodsReceipts\GoodsReceiptResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditGoodsReceipt extends EditRecord
{
    protected static string $resource = GoodsReceiptResource::class;

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
                        app(PostGoodsReceiptAction::class)->execute($this->record);
                        Notification::make()->title(__('erp.notifications.posted'))->success()->send();
                        $this->refreshFormData(['status', 'posted_at', 'posted_by', 'stock_transaction_id']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(collect($e->errors())->flatten()->first() ?? __('erp.notifications.error'))
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make()
                ->visible(fn () => ($this->record->status?->value ?? $this->record->status) === 'draft'),
        ];
    }
}
