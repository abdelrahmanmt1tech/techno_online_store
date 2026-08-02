<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Enums\OperationType;
use App\Filament\Exports\OpeningEntriesExporter;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\Operation;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OpeningEntriesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.accounting-generic-table';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ArrowRightStartOnRectangle;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.opening_entries_report.nav');
    }

    public function getTitle(): string
    {
        return __('dashboard.pages.opening_entries_report.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('opening_entries_report.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('opening_entries_report.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Operation::query()
                ->with(['financialPeriod', 'linkable'])
                ->whereIn('operation_type', [OperationType::OPENING, OperationType::CARRY_FORWARD]))
            ->columns([
                TextColumn::make('id')->label(__('dashboard.resources.operation.operation_no'))->sortable(),
                TextColumn::make('financialPeriod.name')->label(__('dashboard.resources.operation.financial_period'))->searchable(),
                TextColumn::make('operation_type')
                    ->label(__('dashboard.resources.operation.operation_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state),
                TextColumn::make('date')->label(__('dashboard.resources.operation.date'))->date(),
                TextColumn::make('comment')->label(__('dashboard.resources.operation.comment_label'))->wrap(),
                TextColumn::make('total_debit')->label(__('dashboard.resources.operation.total_debit')) ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                TextColumn::make('total_credit')->label(__('dashboard.resources.operation.total_credit')) ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                IconColumn::make('is_locked')->label(__('dashboard.resources.operation.is_locked'))->boolean(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->label(__('dashboard.fields.from_date')),
                        DatePicker::make('to')->label(__('dashboard.fields.to_date')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '>=', $date))
                        ->when($data['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '<=', $date))),
                Filter::make('period')
                    ->schema([
                        Select::make('financial_period_id')
                            ->label(__('dashboard.resources.operation.financial_period'))
                            ->options(fn (): array => FinancialPeriod::query()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['financial_period_id'] ?? null, fn (Builder $q, $id) => $q->where('financial_period_id', $id))),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label(__('dashboard.pages.account_statement.export_excel'))
                    ->exporter(OpeningEntriesExporter::class),
            ]);
    }
}
