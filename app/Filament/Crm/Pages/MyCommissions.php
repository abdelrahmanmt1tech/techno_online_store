<?php

namespace App\Filament\Crm\Pages;

use App\Enums\Crm\CommissionStatus;
use App\Enums\Crm\CommissionType;
use App\Filament\Crm\CrmPage;
use App\Filament\Crm\Exports\OwnCommissionExporter;
use App\Filament\Crm\Pages\MyCommissions\Tables\MyCommissionColumns;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Services\Crm\Commission\OwnCommissionQuery;
use App\Services\Crm\Commission\OwnCommissionTotalsCalculator;
use App\Support\Crm\Commission\OwnCommissionAccess;
use App\Support\Crm\Commission\OwnCommissionVisibility;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyCommissions extends CrmPage implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Wallet;

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.crm.pages.my-commissions';

    public static function getNavigationLabel(): string
    {
        return __('crm.own_commissions.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.commissions');
    }

    public static function canAccessByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser && OwnCommissionAccess::canViewPage($user);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (! $user instanceof TenantUser || ! OwnCommissionAccess::canViewPage($user)) {
            return null;
        }

        $count = OpportunityCommission::query()
            ->forUser($user->id)
            ->whereIn('status', [CommissionStatus::APPROVED, CommissionStatus::PARTIALLY_PAID])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * @return array<string, string|int>
     */
    public function getTotalsProperty(): array
    {
        $user = Auth::user();

        if (! $user instanceof TenantUser) {
            return [];
        }

        return OwnCommissionTotalsCalculator::forUser($user, $this->getFilteredTableQuery());
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();

        if (! $user instanceof TenantUser || ! OwnCommissionAccess::canExport($user)) {
            return [];
        }

        return [
            ExportAction::make()
                ->label(__('crm.own_commissions.actions.export'))
                ->exporter(OwnCommissionExporter::class),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => OwnCommissionQuery::baseForUser(
                Auth::user() instanceof TenantUser ? Auth::user() : abort(403),
            ))
            ->modifyQueryUsing(function (Builder $query): void {
                $includeHistory = (bool) ($this->tableFilters['include_history']['isActive'] ?? false);
                $query->whereIn(
                    'status',
                    OwnCommissionVisibility::statusesForList(includeHistory: $includeHistory),
                );
            })
            ->columns(MyCommissionColumns::make())
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (OpportunityCommission $record): string => ViewMyCommission::getUrl(['commission' => $record->getKey()]))
            ->filters([
                Filter::make('date_range')
                    ->label(__('crm.own_commissions.filters.date_range'))
                    ->schema([
                        Select::make('basis')
                            ->label(__('crm.own_commissions.filters.date_basis'))
                            ->options([
                                'created_at' => __('crm.own_commissions.filters.basis_created_at'),
                                'approved_at' => __('crm.own_commissions.filters.basis_approved_at'),
                                'due_at' => __('crm.own_commissions.filters.basis_due_at'),
                            ])
                            ->default('created_at'),
                        DatePicker::make('from')
                            ->label(__('crm.own_commissions.filters.from_date')),
                        DatePicker::make('to')
                            ->label(__('crm.own_commissions.filters.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $basis = $data['basis'] ?? 'created_at';
                        $column = in_array($basis, ['created_at', 'approved_at', 'due_at'], true)
                            ? $basis
                            : 'created_at';

                        if (! empty($data['from'])) {
                            $query->whereDate($column, '>=', $data['from']);
                        }

                        if (! empty($data['to'])) {
                            $query->whereDate($column, '<=', $data['to']);
                        }

                        return $query;
                    }),
                SelectFilter::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(collect(CommissionStatus::cases())->mapWithKeys(
                        fn (CommissionStatus $status): array => [$status->value => $status->label()],
                    )->all()),
                SelectFilter::make('commission_type')
                    ->label(__('crm.commissions.fields.commission_type'))
                    ->options(collect(CommissionType::cases())->mapWithKeys(
                        fn (CommissionType $type): array => [$type->value => $type->label()],
                    )->all()),
                SelectFilter::make('opportunity_id')
                    ->label(__('crm.fields.opportunity'))
                    ->relationship('opportunity', 'title', fn (Builder $query) => $query->whereIn(
                        'id',
                        OpportunityCommission::query()
                            ->forUser(Auth::id())
                            ->select('opportunity_id'),
                    )),
                SelectFilter::make('branch_id')
                    ->label(__('crm.fields.branch'))
                    ->relationship('branch', 'name', fn (Builder $query) => $query),
                SelectFilter::make('payment_settlement')
                    ->label(__('crm.own_commissions.filters.payment_settlement'))
                    ->options([
                        'fully_paid' => __('crm.own_commissions.filters.fully_paid'),
                        'partially_paid' => __('crm.own_commissions.filters.partially_paid'),
                        'unpaid' => __('crm.own_commissions.filters.unpaid'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => isset($data['value'])
                        ? OwnCommissionQuery::applyPaymentSettlementFilter($query, $data['value'])
                        : $query),
                Filter::make('include_history')
                    ->label(__('crm.own_commissions.filters.include_history'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->paginated([10, 25, 50]);
    }
}
