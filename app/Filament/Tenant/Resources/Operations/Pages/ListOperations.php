<?php

namespace App\Filament\Tenant\Resources\Operations\Pages;

use App\Filament\Exports\GeneralJournalEntriesExporter;
use App\Filament\Tenant\Resources\Operations\OperationResource;
use App\Models\Tenant\Entry;
use App\Services\Reports\JournalEntryExportQueryModifier;
use App\Support\JournalEntryPrintUrl;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use JsonException;

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
                'operation.service',
            ]);
    }

    protected function makeTable(): Table
    {
        return parent::makeTable()
            ->headerActions([
                ExportAction::make()
                    ->label(__('dashboard.pages.account_statement.export_excel'))
                    ->exporter(GeneralJournalEntriesExporter::class)
                    ->fileName(fn (): string => 'operations-'.now()->format('Y-m-d-His'))
                    ->enableVisibleTableColumnsByDefault()
                    ->columnMappingColumns(3)
                    ->modifyQueryUsing(fn (Builder $query, array $options) => JournalEntryExportQueryModifier::apply($query, $options))
                    ->authorize(fn (): bool => Auth::user()?->can('operations.view') ?? false),
                Action::make('printReport')
                    ->label(__('dashboard.pages.general_account_statement.print'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->authorize(fn (): bool => Auth::user()?->can('operations.view') ?? false)
                    ->url(fn (): string => $this->getJournalEntriesPrintUrl())
                    ->openUrlInNewTab(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('operations.create') ?? false),
        ];
    }

    protected function getJournalEntriesPrintUrl(): string
    {
        try {
            return JournalEntryPrintUrl::toRoute([
                'filters' => $this->tableFilters ?? [],
                'sort' => $this->tableSort,
                'grouping' => $this->tableGrouping,
                'include_summaries' => true,
                'printed_by' => Auth::user()?->name ?? Auth::user()?->email ?? '-',
                'printed_by_id' => Auth::id(),
                'locale' => app()->getLocale(),
            ]);
        } catch (JsonException $e) {
            Notification::make()
                ->title(__('dashboard.pages.general_account_statement.print'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return '#';
        }
    }
}
