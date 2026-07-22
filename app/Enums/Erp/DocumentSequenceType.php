<?php

namespace App\Enums\Erp;

enum DocumentSequenceType: string
{
    case Branch = 'branch';
    case Warehouse = 'warehouse';
    case InventoryItem = 'inventory_item';
    case StockReceipt = 'stock_receipt';
    case StockIssue = 'stock_issue';
    case StockTransfer = 'stock_transfer';
    case StockAdjustment = 'stock_adjustment';
    case StockDamage = 'stock_damage';
    case PurchaseOrder = 'purchase_order';
    case GoodsReceipt = 'goods_receipt';
    case PurchaseInvoice = 'purchase_invoice';
    case PurchaseReturn = 'purchase_return';
    case Sale = 'sale';
    case SalesInvoice = 'sales_invoice';
    case SalesReturn = 'sales_return';
    case Payment = 'payment';

    public function prefix(): string
    {
        return match ($this) {
            self::Branch => 'BR',
            self::Warehouse => 'WH',
            self::InventoryItem => 'ITM',
            self::StockReceipt => 'SR',
            self::StockIssue => 'SI',
            self::StockTransfer => 'ST',
            self::StockAdjustment => 'SA',
            self::StockDamage => 'SD',
            self::PurchaseOrder => 'PO',
            self::GoodsReceipt => 'GR',
            self::PurchaseInvoice => 'PI',
            self::PurchaseReturn => 'PR',
            self::Sale => 'SL',
            self::SalesInvoice => 'INV',
            self::SalesReturn => 'SRT',
            self::Payment => 'PAY',
        };
    }
}
