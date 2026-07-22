<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Schemas;

use App\Enums\Erp\StockTransactionType;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return StockTransactionForm::configure(
            $schema,
            allowedTypes: [StockTransactionType::Transfer],
            showSourceWarehouse: true,
            showDestinationWarehouse: true,
            showUnitCost: false,
            lockType: true,
            fixedType: StockTransactionType::Transfer,
        );
    }
}
