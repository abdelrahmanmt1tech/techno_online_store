<?php

namespace App\Filament\Tenant\Resources\Contacts\Schemas;

use App\Models\Tenant\Branch;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('dashboard.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('dashboard.email'))
                    ->required()
                    ->email()
                    ->maxLength(255),

                Grid::make(2)->schema([
                    Select::make('branch_id')
                        ->label(__('dashboard.branch'))
                        ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->native(false),

                    TextInput::make('job')
                        ->label(__('dashboard.job'))
                        ->maxLength(255),
                ]),

                TextInput::make('phone')
                    ->label(__('dashboard.customer_phone'))
                    ->maxLength(255),

                Textarea::make('message')
                    ->label(__('dashboard.message'))
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
