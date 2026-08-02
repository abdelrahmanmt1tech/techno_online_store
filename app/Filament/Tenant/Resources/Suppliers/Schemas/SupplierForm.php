<?php

namespace App\Filament\Tenant\Resources\Suppliers\Schemas;

use App\Models\Tenant\AccountTree;
use App\Models\Tenant\AccountsCenter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label(__('erp.fields.name'))->required()->maxLength(255),
                    TextInput::make('code')->label(__('erp.fields.code'))->maxLength(50),
                    TextInput::make('phone')->label(__('erp.fields.phone'))->tel()->maxLength(50),
                    TextInput::make('email')->label(__('erp.fields.email'))->email()->maxLength(255),
                    TextInput::make('tax_number')->label(__('erp.fields.tax_number'))->maxLength(100),
                    TextInput::make('payment_terms_days')->label(__('erp.fields.payment_terms_days'))->numeric()->default(0)->minValue(0),
                    Toggle::make('is_active')->label(__('erp.fields.is_active'))->default(true),
                    Select::make('account_tree_id')
                        ->label(__('erp.fields.account_tree'))
                        ->options(fn (): array => AccountTree::query()
                            ->orderBy('account_code')
                            ->get()
                            ->mapWithKeys(fn (AccountTree $a) => [
                                $a->id => trim(($a->account_code ? $a->account_code.' — ' : '').$a->account_name),
                            ])
                            ->all())
                        ->searchable()
                        ->preload(),
                    Select::make('accounts_center_id')
                        ->label(__('erp.fields.accounts_center'))
                        ->options(fn (): array => AccountsCenter::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload(),
                    Textarea::make('address')->label(__('erp.fields.address'))->rows(2)->columnSpanFull(),
                    Textarea::make('notes')->label(__('erp.fields.notes'))->rows(2)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
