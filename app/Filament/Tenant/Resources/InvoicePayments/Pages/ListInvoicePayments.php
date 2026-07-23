<?php

namespace App\Filament\Tenant\Resources\InvoicePayments\Pages;

use App\Filament\Tenant\Resources\InvoicePayments\InvoicePaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListInvoicePayments extends ListRecords
{
    protected static string $resource = InvoicePaymentResource::class;
}
