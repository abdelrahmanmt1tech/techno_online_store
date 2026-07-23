<?php

namespace App\Filament\Tenant\Resources\Reviews;

use App\Filament\Tenant\Resources\Reviews\Pages\ListReviews;
use App\Filament\Tenant\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Tenant\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Star;

    protected static ?int $navigationSort = 55;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.store_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.reviews');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.reviews');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.review');
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
