<?php

namespace App\Filament\Tenant\Resources\Contacts;

use App\Filament\Tenant\Resources\Contacts\Pages\CreateContact;
use App\Filament\Tenant\Resources\Contacts\Pages\EditContact;
use App\Filament\Tenant\Resources\Contacts\Pages\ListContacts;
use App\Filament\Tenant\Resources\Contacts\Pages\ViewContact;
use App\Filament\Tenant\Resources\Contacts\Schemas\ContactForm;
use App\Filament\Tenant\Resources\Contacts\Schemas\ContactInfolist;
use App\Filament\Tenant\Resources\Contacts\Tables\ContactsTable;
use App\Models\Tenant\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 190;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.store_group');
    }

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

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
        ];
    }
}
