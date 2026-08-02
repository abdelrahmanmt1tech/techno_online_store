<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\AccountsCenterMovement;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountsCentersReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.accounts-centers-report';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.accounts_centers_report.nav');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('accounts_centers_report.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('accounts_centers_report.view') ?? false;
    }

    public function getTitle(): string
    {
        return __('dashboard.pages.accounts_centers_report.title');
    }

    public function getTableRecordKey(Model|array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['accounts_center_id'] ?? '');
        }

        return (string) ($record->accounts_center_id ?? '');
    }

    public function table(Table $table): Table
    {
        $hasMovementDate = Schema::hasColumn('accounts_center_movements', 'movement_date');

        return $table
            ->query(function () use ($hasMovementDate): Builder {
                $query = AccountsCenterMovement::query()
                    ->select([
                        'accounts_center_id',
                        DB::raw('COALESCE(SUM(amount), 0) AS profit_total'),
                        DB::raw('COUNT(*) AS movements_count'),
                    ])
                    ->with('accountsCenter:id,name,account_tree_id')
                    ->whereIn('movement_type', [
                        'ticket_profit',
                        'reservation_commission',
                        'reservation_margin',
                        'manual_operation',
                    ])
                    ->groupBy('accounts_center_id')
                    ->orderBy('accounts_center_id');

                if ($hasMovementDate) {
                    $query->whereNotNull('movement_date');
                }

                return $query;
            })
            ->columns([
                TextColumn::make('accountsCenter.name')
                    ->label(__('dashboard.pages.accounts_centers_report.account_center'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('profit_total')
                    ->label(__('dashboard.pages.accounts_centers_report.total_profits'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('movements_count')
                    ->label(__('dashboard.pages.accounts_centers_report.movements_count'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->label(__('dashboard.pages.accounts_centers_report.from_date')),
                        DatePicker::make('to')->label(__('dashboard.pages.accounts_centers_report.to_date')),
                    ])
                    ->query(function (Builder $query, array $data) use ($hasMovementDate): Builder {
                        $column = $hasMovementDate ? 'movement_date' : 'created_at';

                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate($column, '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate($column, '<=', $date),
                            );
                    }),
            ]);
    }
}
