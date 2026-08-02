<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Pages;

use App\Enums\OperationType;
use App\Filament\Tenant\Resources\FinancialPeriods\FinancialPeriodResource;
use App\Models\Tenant\Operation;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListOpeningEntries extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = FinancialPeriodResource::class;

    protected string $view = 'filament.resources.financial-periods.pages.list-opening-entries';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return __('dashboard.resources.financial_period.opening_entries') . ' - ' . $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            // [ADDED] إنشاء قيد افتتاحي جديد من صفحة القائمة.
            Action::make('create_opening_entry')
                ->label(__('dashboard.resources.financial_period.create_opening_entry'))
                ->url(fn (): string => FinancialPeriodResource::getUrl('opening-entry', ['record' => $this->getRecord()]))
                ->authorize(fn (): bool => Auth::user()?->can('financial_periods.create_opening_entry') ?? false),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            // [ADDED] عرض القيود الافتتاحية فقط للفترة الحالية.
            ->query(fn (): Builder => Operation::query()
                ->where('financial_period_id', $this->getRecord()->id)
                ->where('operation_type', OperationType::OPENING->value))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('dashboard.resources.operation.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('reference_no')
                    ->label(__('dashboard.resources.financial_period.reference_no'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('comment')
                    ->label(__('dashboard.resources.financial_period.comment'))
                    ->limit(60)
                    ->tooltip(fn ($state) => $state)
                    ->searchable(),
                TextColumn::make('total_debit')
                    ->label(__('dashboard.resources.operation.total_debit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                TextColumn::make('total_credit')
                    ->label(__('dashboard.resources.operation.total_credit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            ])
            ->recordActions([
                // [ADDED] فتح صفحة تعديل مستقلة للقيد الافتتاحي.
                EditAction::make('edit_opening_entry')
                    ->url(fn (Operation $record): string => FinancialPeriodResource::getUrl('opening-entry-edit', [
                        'record' => $this->getRecord(),
                        'operation' => $record,
                    ]))
                    ->authorize(fn (): bool => Auth::user()?->can('financial_periods.create_opening_entry') ?? false),
            ]);
    }
}
