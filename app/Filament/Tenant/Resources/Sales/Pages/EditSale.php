<?php

namespace App\Filament\Tenant\Resources\Sales\Pages;

use App\Filament\Tenant\Resources\Sales\SaleResource;
use App\Services\Commerce\UnifiedSalesEngine;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label(__('erp.actions.confirm'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => ($this->record->status?->value ?? $this->record->status) === 'draft' && ! $this->record->is_suspended)
                ->action(function () {
                    try {
                        app(UnifiedSalesEngine::class)->confirm($this->record);
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
                        app(UnifiedSalesEngine::class)->issueInvoice($this->record);
                        Notification::make()->title(__('erp.notifications.invoice_created'))->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(collect($e->errors())->flatten()->first() ?? __('erp.notifications.error'))
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make()->visible(fn () => ($this->record->status?->value ?? $this->record->status) === 'draft' && Auth::user()->can('erp.sales.manage')),
        ];
    }
}
