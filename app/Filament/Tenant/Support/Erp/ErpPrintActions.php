<?php

namespace App\Filament\Tenant\Support\Erp;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

final class ErpPrintActions
{
    public static function printSalesInvoice(): Action
    {
        return Action::make('printInvoice')
            ->label(__('erp.actions.print'))
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(fn (Model $record): string => route('filament.tenant.erp.sales-invoices.print', ['salesInvoice' => $record]))
            ->openUrlInNewTab();
    }

    public static function printPurchaseInvoice(): Action
    {
        return Action::make('printInvoice')
            ->label(__('erp.actions.print'))
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(fn (Model $record): string => route('filament.tenant.erp.purchase-invoices.print', ['purchaseInvoice' => $record]))
            ->openUrlInNewTab();
    }
}
