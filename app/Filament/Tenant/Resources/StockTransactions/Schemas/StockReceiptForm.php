<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Schemas;

use App\Enums\Erp\StockTransactionType;
use Filament\Schemas\Schema;

class StockReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return StockTransactionForm::configure(
            $schema,
            allowedTypes: [StockTransactionType::OpeningBalance, StockTransactionType::ManualReceipt],
            showSourceWarehouse: false,
            showDestinationWarehouse: true,
            showUnitCost: true,
            lockType: false,
            fixedType: null,
        );
    }
}
