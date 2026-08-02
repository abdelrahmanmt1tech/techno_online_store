<?php

namespace App\Filament\Crm\Pages\Reports\Concerns;

use App\Enums\Crm\ClientStage;
use App\Models\TenantUser;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use JsonException;

trait InteractsWithCrmReportPage
{
    private const PRINT_URL_TTL_MINUTES = 30;

    /** @var array<string, mixed>|null */
    protected ?array $cachedReportSummary = null;

    protected function currentUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @param  callable(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    protected function cachedReportSummary(callable $resolver): array
    {
        if ($this->cachedReportSummary === null) {
            $this->cachedReportSummary = $resolver();
        }

        return $this->cachedReportSummary;
    }

    /**
     * @return array<int, SelectFilter|Filter>
     */
    protected function sharedReportFilters(string $defaultDateBasis, array $dateBasisOptions): array
    {
        return [
            Filter::make('date_range')
                ->label(__('crm.reports.filters.date_range'))
                ->schema([
                    Select::make('basis')
                        ->label(__('crm.reports.filters.date_basis'))
                        ->options($dateBasisOptions)
                        ->default($defaultDateBasis),
                    DatePicker::make('from')
                        ->label(__('crm.reports.filters.from_date')),
                    DatePicker::make('to')
                        ->label(__('crm.reports.filters.to_date')),
                ])
                ->query(fn ($query) => $query),
            SelectFilter::make('branch_id')
                ->label(__('crm.fields.branch'))
                ->relationship('branch', 'name', fn ($query) => $query)
                ->visible(fn (): bool => $this->currentUser()->can('crm_reports.view_all_branches')
                    || $this->currentUser()->branches()->exists()),
            SelectFilter::make('sales_rep_id')
                ->label(__('crm.fields.assigned_to'))
                ->relationship('salesRep', 'name', fn ($query) => $query)
                ->searchable()
                ->preload(),
        ];
    }

    protected function filtersLayout(): FiltersLayout
    {
        return FiltersLayout::AboveContentCollapsible;
    }

    /**
     * @param  class-string  $exporterClass
     * @return array<int, ExportAction>
     */
    protected function exportHeaderAction(string $exporterClass, callable $canExport): array
    {
        if (! $canExport($this->currentUser())) {
            return [];
        }

        return [
            ExportAction::make()
                ->label(__('crm.reports.actions.export'))
                ->exporter($exporterClass)
                ->fileName(fn (): string => 'crm-report-'.now()->format('Y-m-d-His')),
        ];
    }

    protected function printHeaderAction(string $routeName, callable $canPrint): Action
    {
        return Action::make('printReport')
            ->label(__('crm.reports.actions.print'))
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->authorize(fn (): bool => $canPrint($this->currentUser()))
            ->url(fn (): string => $this->buildPrintUrl($routeName))
            ->openUrlInNewTab();
    }

    protected function buildPrintUrl(string $routeName): string
    {
        $user = $this->currentUser();

        $payload = [
            'table_filters' => $this->tableFilters ?? [],
            'printed_by' => $user->name ?? $user->email ?? '-',
            'printed_by_id' => $user->id,
            'locale' => app()->getLocale(),
        ];

        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '#';
        }

        $token = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');

        // Temporary signed URL: any tampering with the token (e.g. branch_id) or expiry
        // invalidates the signature. Permission + branch scope are still enforced server-side
        // in the print controller — the signature is defence-in-depth, not the only guard.
        return URL::temporarySignedRoute(
            $routeName,
            now()->addMinutes(self::PRINT_URL_TTL_MINUTES),
            ['p' => $token],
        );
    }

    protected function amountRangeFilter(): Filter
    {
        return Filter::make('amount_range')
            ->label(__('crm.reports.filters.amount_range'))
            ->schema([
                TextInput::make('from')
                    ->label(__('crm.reports.filters.amount_from'))
                    ->numeric(),
                TextInput::make('to')
                    ->label(__('crm.reports.filters.amount_to'))
                    ->numeric(),
            ])
            ->query(fn ($query) => $query);
    }

    protected function clientStageFilter(): SelectFilter
    {
        return SelectFilter::make('stage')
            ->label(__('crm.fields.stage'))
            ->options(ClientStage::options());
    }

    protected function leadSourceFilter(): SelectFilter
    {
        return SelectFilter::make('lead_source_id')
            ->label(__('crm.fields.source'))
            ->relationship('leadSource', 'name', fn ($query) => $query);
    }

    protected function hasOpportunitiesFilters(): array
    {
        return [
            SelectFilter::make('has_opportunities')
                ->label(__('crm.reports.filters.has_opportunities'))
                ->options([
                    '1' => __('crm.reports.common.yes'),
                    '0' => __('crm.reports.common.no'),
                ])
                ->query(fn ($query) => $query),
            SelectFilter::make('has_won_opportunity')
                ->label(__('crm.reports.filters.has_won_opportunity'))
                ->options([
                    '1' => __('crm.reports.common.yes'),
                    '0' => __('crm.reports.common.no'),
                ])
                ->query(fn ($query) => $query),
        ];
    }
}
