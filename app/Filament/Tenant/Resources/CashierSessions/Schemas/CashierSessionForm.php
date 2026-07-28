<?php

namespace App\Filament\Tenant\Resources\CashierSessions\Schemas;

use App\Enums\Pos\CashierSessionStatus;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashierSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    Select::make('pos_register_id')
                        ->label(__('commerce.fields.register'))
                        ->relationship('register', 'name')
                        ->native(false),
                    Select::make('branch_id')
                        ->label(__('erp.fields.branch'))
                        ->relationship('branch', 'name')
                        ->native(false),
                    Select::make('user_id')
                        ->label(__('commerce.fields.cashier'))
                        ->relationship('user', 'name')
                        ->native(false),
                    Select::make('status')
                        ->label(__('erp.fields.status'))
                        ->options(ErpEnumOptions::options(CashierSessionStatus::class))
                        ->native(false),
                    TextInput::make('device_name')->label(__('commerce.fields.device_name')),
                    DateTimePicker::make('opened_at')->label(__('commerce.fields.opened_at')),
                    DateTimePicker::make('closed_at')->label(__('commerce.fields.closed_at')),
                    TextInput::make('opening_balance')->label(__('commerce.fields.opening_balance'))->numeric(),
                    TextInput::make('expected_balance')->label(__('commerce.fields.expected_balance'))->numeric(),
                    TextInput::make('actual_balance')->label(__('commerce.fields.actual_balance'))->numeric(),
                    TextInput::make('difference')->label(__('commerce.fields.difference'))->numeric(),
                    Textarea::make('opening_notes')->label(__('commerce.fields.opening_notes'))->rows(2),
                    Textarea::make('closing_notes')->label(__('commerce.fields.closing_notes'))->rows(2),
                    Textarea::make('difference_reason')->label(__('commerce.fields.difference_reason'))->rows(2),
                ])
                ->columnSpanFull(),
        ]);
    }
}
