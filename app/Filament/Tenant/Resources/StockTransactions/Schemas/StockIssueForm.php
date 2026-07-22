<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Schemas;

use App\Enums\Erp\StockTransactionType;
use Filament\Schemas\Schema;

class StockIssueForm
{
    public static function configure(Schema $schema): Schema
    {
        return StockTransactionForm::configure(
            $schema,
            allowedTypes: [StockTransactionType::ManualIssue],
            showSourceWarehouse: true,
            showDestinationWarehouse: false,
            showUnitCost: false,
            lockType: true,
            fixedType: StockTransactionType::ManualIssue,
        );
    }
}
