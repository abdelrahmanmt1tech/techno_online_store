<?php

namespace App\Http\Controllers\Tenant\Erp;

use App\Filament\Tenant\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\Tenant\SalesInvoice;
use App\Services\Erp\InvoicePrintDataBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SalesInvoicePrintController
{
    public function __invoke(Request $request, SalesInvoice $salesInvoice, InvoicePrintDataBuilder $builder): View
    {
        $builder->ensureSnapshot($salesInvoice);
        $salesInvoice->refresh();

        $data = $builder->forSalesInvoice(
            $salesInvoice,
            $request->query('locale') ? (string) $request->query('locale') : null,
        );

        return view('erp.invoices.sales', [
            'data' => $data,
            'autoprint' => $request->boolean('autoprint'),
            'backUrl' => SalesInvoiceResource::getUrl('view', ['record' => $salesInvoice]),
        ]);
    }
}
