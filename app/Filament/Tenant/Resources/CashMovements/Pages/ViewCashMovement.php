<?php

namespace App\Filament\Tenant\Resources\CashMovements\Pages;

use App\Filament\Tenant\Resources\CashMovements\CashMovementResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewCashMovement extends ViewRecord
{
    protected static string $resource = CashMovementResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextEntry::make('id'),
                    TextEntry::make('type')->formatStateUsing(fn ($state) => is_object($state) && method_exists($state, 'label') ? $state->label() : (string) $state),
                    TextEntry::make('direction'),
                    TextEntry::make('amount'),
                    TextEntry::make('payment_method_type'),
                    TextEntry::make('payment_method_code'),
                    TextEntry::make('is_reversal')->boolean(),
                    TextEntry::make('reference'),
                    TextEntry::make('notes')->columnSpanFull(),
                    TextEntry::make('created_at')->dateTime(),
                ]),
        ]);
    }
}
