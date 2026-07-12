<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\Resources\Contacts\Pages\CreateContact;
use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Contacts\Pages\ViewContact;
use App\Filament\Resources\Contacts\Schemas\ContactForm;
use App\Filament\Resources\Contacts\Schemas\ContactInfolist;
use App\Filament\Resources\Contacts\Tables\ContactsTable;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 190;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.contacts');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.contacts');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.contact');
    }

    // public static function getNavigationGroup(): ?string
    // {
    //     return __('dashboard.nav_group_site_content');
    // }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('contacts.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('contacts.create');
    }

    public static function canEdit(?Model $record): bool
    {
        return Auth::user()->can('contacts.update');
    }

    public static function canDelete(?Model $record): bool
    {
        return Auth::user()->can('contacts.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return ContactForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            // 'create' => CreateContact::route('/create'),
            // 'view' => ViewContact::route('/{record}'),
            // 'edit' => EditContact::route('/{record}/edit'),
        ];
    }
}
