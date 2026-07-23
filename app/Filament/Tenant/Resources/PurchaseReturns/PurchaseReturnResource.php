<?php

namespace App\Filament\Tenant\Resources\PurchaseReturns;

use App\Filament\Tenant\Resources\PurchaseReturns\Pages\CreatePurchaseReturn;
use App\Filament\Tenant\Resources\PurchaseReturns\Pages\EditPurchaseReturn;
use App\Filament\Tenant\Resources\PurchaseReturns\Pages\ListPurchaseReturns;
use App\Filament\Tenant\Resources\PurchaseReturns\Pages\ViewPurchaseReturn;
use App\Filament\Tenant\Resources\PurchaseReturns\Schemas\PurchaseReturnForm;
use App\Filament\Tenant\Resources\PurchaseReturns\Tables\PurchaseReturnsTable;
use App\Models\Tenant\PurchaseReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseReturnResource extends Resource
{
    protected static ?string $model = PurchaseReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUturnLeft;

    protected static ?int $navigationSort = 323;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.purchases');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.purchase_returns');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.purchase_returns');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.purchase_return');
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseReturnForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseReturnsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseReturns::route('/'),
            'create' => CreatePurchaseReturn::route('/create'),
            'view' => ViewPurchaseReturn::route('/{record}'),
            'edit' => EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}
