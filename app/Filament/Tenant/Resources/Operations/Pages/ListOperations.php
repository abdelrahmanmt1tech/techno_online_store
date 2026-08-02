<?php

namespace App\Filament\Tenant\Resources\Operations\Pages;

use App\Filament\Tenant\Resources\Operations\OperationResource;
use App\Models\Tenant\Entry;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListOperations extends ListRecords
{
    protected static string $resource = OperationResource::class;

    protected function getTableQuery(): Builder
    {
        return Entry::query()
            ->with([
                'accountTree',
                'operation',
                'operation.linkable',
            ]);
    }

    protected function makeTable(): Table
    {
        return parent::makeTable();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('operations.create') ?? false),
        ];
    }
}
