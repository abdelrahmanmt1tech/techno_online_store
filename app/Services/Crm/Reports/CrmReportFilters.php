<?php

namespace App\Services\Crm\Reports;

use App\Enums\Crm\ClientStage;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Campaign;
use App\Models\Tenant\Client;
use App\Models\Tenant\FollowUpStatus;
use App\Models\Tenant\FollowUpType;
use App\Models\Tenant\LeadSource;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityStage;
use App\Models\TenantUser;

final class CrmReportFilters
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public string $dateBasis = 'created_at',
        public ?int $branchId = null,
        public ?int $salesRepId = null,
        public ?int $leadSourceId = null,
        public ?string $clientStage = null,
        public ?int $campaignId = null,
        public ?int $opportunityStageId = null,
        public ?string $opportunityStatus = null,
        public ?int $clientId = null,
        public ?int $opportunityId = null,
        public ?string $amountFrom = null,
        public ?string $amountTo = null,
        public ?bool $hasOpportunities = null,
        public ?bool $hasWonOpportunity = null,
        public ?int $followUpTypeId = null,
        public ?int $followUpStatusId = null,
        public ?string $followUpScheduling = null,
        public ?string $campaignStatus = null,
    ) {}

    /**
     * @param  array<string, mixed>  $tableFilters
     */
    public static function fromTableFilters(array $tableFilters, string $defaultDateBasis = 'created_at'): self
    {
        $dateRange = is_array($tableFilters['date_range'] ?? null) ? $tableFilters['date_range'] : [];
        $amountRange = is_array($tableFilters['amount_range'] ?? null) ? $tableFilters['amount_range'] : [];

        return new self(
            from: self::stringOrNull($dateRange['from'] ?? null),
            to: self::stringOrNull($dateRange['to'] ?? null),
            dateBasis: self::stringOrNull($dateRange['basis'] ?? null) ?? $defaultDateBasis,
            branchId: self::intOrNull($tableFilters['branch_id']['value'] ?? null),
            salesRepId: self::intOrNull($tableFilters['sales_rep_id']['value'] ?? null),
            leadSourceId: self::intOrNull($tableFilters['lead_source_id']['value'] ?? null),
            clientStage: self::stringOrNull($tableFilters['stage']['value'] ?? null),
            campaignId: self::intOrNull($tableFilters['campaign_id']['value'] ?? null),
            opportunityStageId: self::intOrNull($tableFilters['opportunity_stage_id']['value'] ?? null),
            opportunityStatus: self::stringOrNull($tableFilters['opportunity_status']['value'] ?? null),
            clientId: self::intOrNull($tableFilters['client_id']['value'] ?? null),
            opportunityId: self::intOrNull($tableFilters['opportunity_id']['value'] ?? null),
            amountFrom: self::stringOrNull($amountRange['from'] ?? null),
            amountTo: self::stringOrNull($amountRange['to'] ?? null),
            hasOpportunities: self::triStateOrNull($tableFilters['has_opportunities']['value'] ?? null),
            hasWonOpportunity: self::triStateOrNull($tableFilters['has_won_opportunity']['value'] ?? null),
            followUpTypeId: self::intOrNull($tableFilters['follow_up_type_id']['value'] ?? null),
            followUpStatusId: self::intOrNull($tableFilters['follow_up_status_id']['value'] ?? null),
            followUpScheduling: self::stringOrNull($tableFilters['follow_up_scheduling']['value'] ?? null),
            campaignStatus: self::stringOrNull($tableFilters['campaign_status']['value'] ?? null),
        );
    }

    protected static function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    protected static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected static function triStateOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value === '0' || $value === 0 || $value === false || $value === 'no') {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $tableFilters
     * @return list<string>
     */
    public static function summarizeForPrint(array $tableFilters, string $defaultDateBasis = 'created_at'): array
    {
        $filters = self::fromTableFilters($tableFilters, $defaultDateBasis);
        $lines = [];

        $lines[] = __('crm.reports.print.date_basis', [
            'basis' => self::dateBasisLabel($filters->dateBasis),
        ]);

        if ($filters->from !== null || $filters->to !== null) {
            $lines[] = __('crm.reports.print.date_range', [
                'from' => $filters->from ?? '—',
                'to' => $filters->to ?? '—',
            ]);
        }

        if ($filters->branchId !== null) {
            $lines[] = __('crm.reports.print.branch', [
                'value' => self::branchLabel($filters->branchId),
            ]);
        }

        if ($filters->salesRepId !== null) {
            $lines[] = __('crm.reports.print.employee', [
                'value' => self::userLabel($filters->salesRepId),
            ]);
        }

        if ($filters->leadSourceId !== null) {
            $lines[] = __('crm.reports.print.source', [
                'value' => self::leadSourceLabel($filters->leadSourceId),
            ]);
        }

        if ($filters->clientStage !== null) {
            $lines[] = __('crm.reports.print.client_stage', [
                'value' => ClientStage::tryFrom($filters->clientStage)?->label() ?? $filters->clientStage,
            ]);
        }

        if ($filters->campaignId !== null) {
            $lines[] = __('crm.reports.print.campaign', [
                'value' => self::campaignLabel($filters->campaignId),
            ]);
        }

        if ($filters->campaignStatus !== null) {
            $lines[] = __('crm.reports.print.campaign_status', [
                'value' => __('crm.campaign_status_options.'.$filters->campaignStatus),
            ]);
        }

        if ($filters->opportunityStageId !== null) {
            $lines[] = __('crm.reports.print.opportunity_stage', [
                'value' => self::opportunityStageLabel($filters->opportunityStageId),
            ]);
        }

        if ($filters->opportunityStatus !== null) {
            $lines[] = __('crm.reports.print.opportunity_status', [
                'value' => match ($filters->opportunityStatus) {
                    'open' => __('crm.reports.filters.status_open'),
                    'won' => __('crm.reports.filters.status_won'),
                    'lost' => __('crm.reports.filters.status_lost'),
                    default => $filters->opportunityStatus,
                },
            ]);
        }

        if ($filters->clientId !== null) {
            $lines[] = __('crm.reports.print.client', [
                'value' => self::clientLabel($filters->clientId),
            ]);
        }

        if ($filters->opportunityId !== null) {
            $lines[] = __('crm.reports.print.opportunity', [
                'value' => self::opportunityLabel($filters->opportunityId),
            ]);
        }

        if ($filters->amountFrom !== null || $filters->amountTo !== null) {
            $lines[] = __('crm.reports.print.amount_range', [
                'from' => $filters->amountFrom ?? '—',
                'to' => $filters->amountTo ?? '—',
            ]);
        }

        if ($filters->hasOpportunities !== null) {
            $lines[] = __('crm.reports.print.has_opportunities', [
                'value' => $filters->hasOpportunities
                    ? __('crm.reports.common.yes')
                    : __('crm.reports.common.no'),
            ]);
        }

        if ($filters->hasWonOpportunity !== null) {
            $lines[] = __('crm.reports.print.has_won_opportunity', [
                'value' => $filters->hasWonOpportunity
                    ? __('crm.reports.common.yes')
                    : __('crm.reports.common.no'),
            ]);
        }

        if ($filters->followUpTypeId !== null) {
            $lines[] = __('crm.reports.print.follow_up_type', [
                'value' => self::followUpTypeLabel($filters->followUpTypeId),
            ]);
        }

        if ($filters->followUpStatusId !== null) {
            $lines[] = __('crm.reports.print.follow_up_status', [
                'value' => self::followUpStatusLabel($filters->followUpStatusId),
            ]);
        }

        if ($filters->followUpScheduling !== null) {
            $lines[] = __('crm.reports.print.follow_up_scheduling', [
                'value' => __('crm.reports.followup.scheduling.'.$filters->followUpScheduling),
            ]);
        }

        return $lines;
    }

    protected static function dateBasisLabel(string $basis): string
    {
        return match ($basis) {
            'created_at' => __('crm.reports.filters.basis_created_at'),
            'updated_at' => __('crm.reports.filters.basis_updated_at'),
            'closed_at' => __('crm.reports.filters.basis_closed_at'),
            'scheduled_at' => __('crm.reports.filters.basis_scheduled_at'),
            'completed_at' => __('crm.reports.filters.basis_completed_at'),
            'start_date' => __('crm.reports.filters.basis_start_date'),
            'approved_at' => __('crm.reports.filters.basis_approved_at'),
            'clients.created_at' => __('crm.reports.filters.basis_client_created_at'),
            default => $basis,
        };
    }

    protected static function branchLabel(int $id): string
    {
        $branch = Branch::query()->find($id);

        return self::translatableName($branch?->name) ?? (string) $id;
    }

    protected static function userLabel(int $id): string
    {
        return TenantUser::query()->whereKey($id)->value('name') ?? (string) $id;
    }

    protected static function leadSourceLabel(int $id): string
    {
        $source = LeadSource::query()->find($id);

        return self::translatableName($source?->name) ?? (string) $id;
    }

    protected static function campaignLabel(int $id): string
    {
        $campaign = Campaign::query()->find($id);

        return self::translatableName($campaign?->name) ?? (string) $id;
    }

    protected static function clientLabel(int $id): string
    {
        $client = Client::query()->find($id);

        return self::translatableName($client?->name) ?? (string) $id;
    }

    protected static function opportunityLabel(int $id): string
    {
        return Opportunity::query()->whereKey($id)->value('title') ?? (string) $id;
    }

    protected static function opportunityStageLabel(int $id): string
    {
        $stage = OpportunityStage::query()->find($id);

        return self::translatableName($stage?->name) ?? (string) $id;
    }

    protected static function followUpTypeLabel(int $id): string
    {
        $type = FollowUpType::query()->find($id);

        return self::translatableName($type?->name) ?? (string) $id;
    }

    protected static function followUpStatusLabel(int $id): string
    {
        $status = FollowUpStatus::query()->find($id);

        return self::translatableName($status?->name) ?? (string) $id;
    }

    protected static function translatableName(mixed $name): ?string
    {
        if ($name === null) {
            return null;
        }

        if (is_array($name)) {
            return $name[app()->getLocale()] ?? reset($name) ?: null;
        }

        return (string) $name;
    }
}
