<?php

namespace App\Filament\Tenant\Resources\Sales\Pages;

use App\Actions\Erp\ConfirmSaleAction;
use App\Actions\Erp\CreateSalesInvoiceAction;
use App\Filament\Tenant\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label(__('erp.actions.confirm'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => ($this->record->status?->value ?? $this->record->status) === 'draft')
                ->action(function () {
                    try {
                        app(ConfirmSaleAction::class)->execute($this->record);
                        Notification::make()->title(__('erp.notifications.confirmed'))->success()->send();
                        $this->refreshFormData(['status', 'confirmed_at', 'confirmed_by', 'subtotal', 'grand_total', 'cost_total', 'profit_total']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(collect($e->errors())->flatten()->first() ?? __('erp.notifications.error'))
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('createInvoice')
                ->label(__('erp.actions.create_invoice'))
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status?->value ?? $this->record->status, [
                    'confirmed', 'partially_invoiced', 'partially_returned',
                ], true))
                ->action(function () {
                    try {
                        app(CreateSalesInvoiceAction::class)->execute($this->record);
                        Notification::make()->title(__('erp.notifications.invoice_created'))->success()->send();
                        $this->refreshFormData(['status']);
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
