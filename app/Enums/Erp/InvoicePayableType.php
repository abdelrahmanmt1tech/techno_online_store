<?php

namespace App\Enums\Erp;

enum InvoicePayableType: string
{
    case SalesInvoice = 'sales_invoice';
    case PurchaseInvoice = 'purchase_invoice';
}
