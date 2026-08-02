<?php

namespace App\Filament\Tenant\Resources\Clients\Schemas;

use App\Enums\Crm\ClientStage;
use App\Models\TenantUser;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('crm.fields.basic_info'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label(__('crm.fields.name'))
                                ->required()
                                ->columnSpanFull(),
                            TextInput::make('company_name')
                                ->label(__('crm.fields.company_name')),
                            TextInput::make('gondc_name')
                                ->label(__('crm.fields.gondc_name')),
                            TextInput::make('email')
                                ->label(__('crm.fields.email'))
                                ->email(),
                            TextInput::make('phone')
                                ->label(__('crm.fields.phone'))
                                ->tel(),
                            TextInput::make('tax_number')
                                ->label(__('crm.fields.tax_number')),
                            TextInput::make('commercial_register')
                                ->label(__('crm.fields.commercial_register')),
                            Textarea::make('address')
                                ->label(__('crm.fields.address'))
                                ->columnSpanFull(),
                            Select::make('stage')
                                ->label(__('crm.fields.stage'))
                                ->options(collect(ClientStage::cases())->mapWithKeys(
                                    fn (ClientStage $s) => [$s->value => $s->name]
                                )->all())
                                ->searchable(),
                            Select::make('sales_rep_id')
                                ->label(__('crm.fields.sales_rep'))
                                ->options(fn (): array => TenantUser::query()
                                    ->where('is_admin', true)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload(),
                            Select::make('lead_source_id')
                                ->relationship('leadSource', 'name')
                                ->label(__('crm.fields.lead_source'))
                                ->searchable()
                                ->preload(),
                            Select::make('first_followed_by')
                                ->label(__('crm.fields.first_followed_by'))
                                ->options(fn (): array => TenantUser::query()
                                    ->where('is_admin', true)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload(),
                            Toggle::make('is_provisional')
                                ->label(__('crm.fields.is_provisional')),
                        ]),
                    ]),
            ]);
    }
}
