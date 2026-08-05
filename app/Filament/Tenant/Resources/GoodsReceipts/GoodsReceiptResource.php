<?php

namespace App\Filament\Tenant\Resources\GoodsReceipts;

use App\Filament\Tenant\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Tenant\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Tenant\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Filament\Tenant\Resources\GoodsReceipts\Pages\ViewGoodsReceipt;
use App\Filament\Tenant\Resources\GoodsReceipts\Schemas\GoodsReceiptForm;
use App\Filament\Tenant\Resources\GoodsReceipts\Tables\GoodsReceiptsTable;
use App\Models\Tenant\GoodsReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArchiveBox;

    protected static ?int $navigationSort = 321;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.purchases');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.goods_receipts');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.goods_receipts');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.goods_receipt');
    }

    public static function canViewAny(): bool
    {
        return tenant_module_enabled(\App\Support\Modules\TenantModule::Pos) && (Auth::user()->can('erp.goods_receipts.view'));
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('erp.goods_receipts.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('erp.goods_receipts.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('erp.goods_receipts.manage');
    }

    public static function form(Schema $schema): Schema
    {
        return GoodsReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoodsReceiptsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoodsReceipts::route('/'),
            'create' => CreateGoodsReceipt::route('/create'),
            'view' => ViewGoodsReceipt::route('/{record}'),
            'edit' => EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
