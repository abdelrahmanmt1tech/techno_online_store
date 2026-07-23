<?php

namespace App\Filament\Tenant\Resources\PurchaseOrders\Pages;

use App\Actions\Erp\ApprovePurchaseOrderAction;
use App\Filament\Tenant\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('erp.actions.approve'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => ($this->record->status?->value ?? $this->record->status) === 'draft')
                ->action(function () {
                    try {
                        app(ApprovePurchaseOrderAction::class)->execute($this->record);
                        Notification::make()->title(__('erp.notifications.approved'))->success()->send();
                        $this->refreshFormData(['status', 'approved_at', 'approved_by', 'subtotal', 'discount_total', 'tax_total', 'grand_total']);
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
