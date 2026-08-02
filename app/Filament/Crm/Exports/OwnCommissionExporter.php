<?php

namespace App\Filament\Crm\Exports;

use App\Enums\Crm\CommissionStatus;
use App\Enums\Crm\CommissionType;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Services\Crm\Commission\OwnCommissionQuery;
use App\Support\Crm\Commission\OwnCommissionAccess;
use App\Support\Export\SafeExportColumn;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class OwnCommissionExporter extends Exporter
{
    protected static ?string $model = OpportunityCommission::class;

    public static function getName(): string
    {
        return __('crm.own_commissions.export.name');
    }

    public static function getColumns(): array
    {
        return [
            SafeExportColumn::text('opportunity.title', __('crm.fields.opportunity')),
            SafeExportColumn::translatable('opportunity.client.name', __('crm.fields.client')),
            ExportColumn::make('commission_type')
                ->label(__('crm.commissions.fields.commission_type'))
                ->formatStateUsing(fn (CommissionType $state): string => $state->label()),
            ExportColumn::make('commission_amount')
                ->label(__('crm.own_commissions.fields.original_amount')),
            ExportColumn::make('effective_amount')
                ->label(__('crm.own_commissions.fields.effective_amount'))
                ->state(fn (OpportunityCommission $record): string => $record->effectiveCommissionAmount()),
            ExportColumn::make('net_paid')
                ->label(__('crm.own_commissions.fields.net_paid'))
                ->state(fn (OpportunityCommission $record): string => $record->netPaidAmount()),
            ExportColumn::make('remaining_amount')
                ->label(__('crm.own_commissions.fields.remaining')),
            ExportColumn::make('status')
                ->label(__('crm.fields.status'))
                ->formatStateUsing(fn (CommissionStatus $state): string => $state->label()),
            ExportColumn::make('approved_at')
                ->label(__('crm.own_commissions.fields.approved_at')),
            ExportColumn::make('due_at')
                ->label(__('crm.own_commissions.fields.due_at')),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        $user = auth()->user();

        abort_unless($user instanceof User && OwnCommissionAccess::canExport($user), 403);

        return OwnCommissionQuery::forUser($user, includeHistory: true)
            ->orderByDesc('created_at');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('crm.own_commissions.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
