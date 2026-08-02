<?php

namespace App\Filament\Tenant\Resources\LeadSources\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LeadSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('dashboard.fields.name')),
                ColumnGroup::make(__('dashboard.resources.lead_source.performance'), [
                    TextColumn::make('clients_count')
                        ->label(__('dashboard.resources.lead_source.clients_count'))
                        ->badge()
                        ->color('primary')
                        ->sortable()
                        ->summarize(Sum::make()->label(__('dashboard.resources.lead_source.total'))),
                    TextColumn::make('opportunities_count')
                        ->label(__('dashboard.resources.lead_source.opportunities_count'))
                        ->badge()
                        ->color('info')
                        ->sortable()
                        ->summarize(Sum::make()->label(__('dashboard.resources.lead_source.total'))),
                    TextColumn::make('won_opportunities_count')
                        ->label(__('dashboard.resources.lead_source.won_opportunities_count'))
                        ->badge()
                        ->color('success')
                        ->sortable()
                        ->summarize(Sum::make()->label(__('dashboard.resources.lead_source.total'))),
                    TextColumn::make('won_agreed_amount_total')
                        ->label(__('dashboard.resources.lead_source.won_agreed_amount_total'))
                        ->numeric(decimalPlaces: 2)
                        ->sortable()
                        ->suffix(' SAR')
                        ->summarize(
                            Sum::make()
                                ->label(__('dashboard.resources.lead_source.total'))
                                ->numeric(decimalPlaces: 2),
                        ),
                ])->alignCenter()->wrapHeader(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('lead_sources.update') ?? false),
                DeleteAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('lead_sources.delete') ?? false),
                RestoreAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('lead_sources.restore') ?? false),
                ForceDeleteAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('lead_sources.force_delete') ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn (): bool => Auth::user()?->can('lead_sources.delete_bulk') ?? false),
                    RestoreBulkAction::make()
                        ->authorize(fn (): bool => Auth::user()?->can('lead_sources.restore_bulk') ?? false),
                    ForceDeleteBulkAction::make()
                        ->authorize(fn (): bool => Auth::user()?->can('lead_sources.force_delete_bulk') ?? false),
                ]),
            ]);
    }
}
