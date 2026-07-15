<?php

namespace App\Filament\Tenant\Resources\Coupons;

use App\Filament\Tenant\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Tenant\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Tenant\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Tenant\Resources\Coupons\Schemas\CouponForm;
use App\Filament\Tenant\Resources\Coupons\Tables\CouponsTable;
use App\Models\Tenant\Coupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Ticket;

    protected static ?int $navigationSort = 52;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.store_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.coupons');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.coupons');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.coupon');
    }

    public static function form(Schema $schema): Schema
    {
        return CouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
