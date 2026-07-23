<?php

namespace App\Http\Controllers\Tenant\Erp;

use App\Filament\Tenant\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Models\Tenant\PurchaseInvoice;
use App\Services\Erp\InvoicePrintDataBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PurchaseInvoicePrintController
{
    public function __invoke(Request $request, PurchaseInvoice $purchaseInvoice, InvoicePrintDataBuilder $builder): View
    {
        $builder->ensureSnapshot($purchaseInvoice);
        $purchaseInvoice->refresh();

        $data = $builder->forPurchaseInvoice(
            $purchaseInvoice,
            $request->query('locale') ? (string) $request->query('locale') : null,
        );

        return view('erp.invoices.purchase', [
            'data' => $data,
            'autoprint' => $request->boolean('autoprint'),
            'backUrl' => PurchaseInvoiceResource::getUrl('view', ['record' => $purchaseInvoice]),
        ]);
    }
}
