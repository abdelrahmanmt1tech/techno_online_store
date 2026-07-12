<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Filament\Resources\Faqs\Schemas\FaqForm;
use App\Filament\Resources\Faqs\Tables\FaqsTable;
use App\Models\Faq;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QuestionMarkCircle;

    protected static ?int $navigationSort = 150;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.faqs');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.faqs');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.faq');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('faqs.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('faqs.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('faqs.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('faqs.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return FaqForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }
}



