<?php

namespace App\Filament\Tenant\Resources\GoodsReceipts\Pages;

use App\Filament\Tenant\Resources\GoodsReceipts\GoodsReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListGoodsReceipts extends ListRecords
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()
            ->visible(fn () => Auth::user()->can('erp.goods_receipts.manage'))];
    }
}
