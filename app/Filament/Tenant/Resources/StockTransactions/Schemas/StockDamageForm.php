<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Schemas;

use App\Enums\Erp\StockTransactionType;
use Filament\Schemas\Schema;

class StockDamageForm
{
    public static function configure(Schema $schema): Schema
    {
        return StockTransactionForm::configure(
            $schema,
            allowedTypes: [StockTransactionType::Damage],
            showSourceWarehouse: true,
            showDestinationWarehouse: false,
            showUnitCost: false,
            lockType: true,
            fixedType: StockTransactionType::Damage,
        );
    }
}
