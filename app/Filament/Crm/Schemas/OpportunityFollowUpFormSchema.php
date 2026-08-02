<?php

namespace App\Filament\Crm\Schemas;

use App\Enums\Crm\FollowUpStatusAction;
use App\Models\Tenant\FollowUpStatus;
use App\Models\Tenant\FollowUpType;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityFollowUp;
use App\Models\Tenant\OpportunityStage;
use App\Models\TenantUser;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;

class OpportunityFollowUpFormSchema
{
    public static function configure(bool $includeOpportunity = true): array
    {
        $components = [];

        if ($includeOpportunity) {
            $components[] = Section::make(__('crm.sections.relations'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('opportunity_id')
                        ->label(__('crm.fields.opportunity'))
                        ->options(fn (): array => Opportunity::query()
                            ->with('client')
                            ->latest()
                            ->limit(200)
                            ->get()
                            ->mapWithKeys(fn (Opportunity $opportunity): array => [
                                $opportunity->id => $opportunity->title . ' · ' . ($opportunity->client?->name ?? '-'),
                            ])
                            ->all())
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => Opportunity::query()
                            ->with('client')
                            ->where('title', 'like', "%{$search}%")
                            ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Opportunity $opportunity): array => [
                                $opportunity->id => $opportunity->title . ' · ' . ($opportunity->client?->name ?? '-'),
                            ])
                            ->all())
                        ->getOptionLabelUsing(function ($value): ?string {
                            $opportunity = Opportunity::query()->with('client')->find($value);

                            return $opportunity
                                ? $opportunity->title . ' · ' . ($opportunity->client?->name ?? '-')
                                : null;
                        })
                        ->required()
                        ->columnSpanFull(),
                    Select::make('parent_follow_up_id')
                        ->label(__('crm.fields.parent_follow_up'))
                        ->options(fn (callable $get): array => OpportunityFollowUp::query()
                            ->when($get('opportunity_id'), fn ($q, $id) => $q->where('opportunity_id', $id))
                            ->latest()
                            ->limit(100)
                            ->get()
                            ->mapWithKeys(fn ($followUp): array => [
                                $followUp->id => '#' . $followUp->id . ' · ' . ($followUp->scheduled_at?->format('Y-m-d') ?? '-'),
                            ])
                            ->all())
                        ->searchable()
                        ->placeholder('-'),
                ]);
        }

        $components[] = Section::make(__('crm.sections.details'))
            ->columns(4)
            ->columnSpanFull()
            ->schema([
                Hidden::make('created_by'),
                Select::make('follow_up_type_id')
                    ->label(__('crm.fields.follow_up_type'))
                    ->options(fn (): array => FollowUpType::query()->pluck('name', 'id')->all())
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('follow_up_status_id')
                    ->label(__('crm.fields.follow_up_status'))
                    ->options(fn (): array => FollowUpStatus::query()->pluck('name', 'id')->all())
                    ->required()
                    ->live()
                    ->searchable()
                    ->preload(),
                Select::make('target_opportunity_stage_id')
                    ->label(__('crm.fields.target_stage'))
                    ->options(fn (): array => OpportunityStage::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get): bool => self::statusActionIs($get('follow_up_status_id'), FollowUpStatusAction::CHANGE_STAGE))
                    ->required(fn (callable $get): bool => self::statusActionIs($get('follow_up_status_id'), FollowUpStatusAction::CHANGE_STAGE)),
                DateTimePicker::make('next_scheduled_at')
                    ->label(__('crm.fields.next_scheduled_at'))
                    ->helperText(__('crm.hints.next_scheduled_at'))
                    ->minDate(now())
                    ->visible(fn (callable $get): bool => self::statusActionIs($get('follow_up_status_id'), FollowUpStatusAction::SCHEDULE_NEXT))
                    ->required(fn (callable $get): bool => self::statusActionIs($get('follow_up_status_id'), FollowUpStatusAction::SCHEDULE_NEXT)),
                Select::make('next_assigned_to')
                    ->label(__('crm.fields.next_assigned_to'))
                    ->helperText(__('crm.hints.next_assigned_to'))
                    ->options(fn (): array => TenantUser::query()->pluck('name', 'id')->all())
                    ->default(fn (): ?int => Auth::id())
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get): bool => self::statusActionIs($get('follow_up_status_id'), FollowUpStatusAction::SCHEDULE_NEXT)),
                Select::make('next_follow_up_type_id')
                    ->label(__('crm.fields.next_follow_up_type'))
                    ->options(fn (): array => FollowUpType::query()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get): bool => self::statusActionIs($get('follow_up_status_id'), FollowUpStatusAction::SCHEDULE_NEXT)),
                Select::make('assigned_to')
                    ->label(__('crm.fields.assigned_to'))
                    ->options(fn (): array => TenantUser::query()->pluck('name', 'id')->all())
                    ->default(fn (): ?int => Auth::id())
                    ->required()
                    ->searchable()
                    ->preload(),
                DatePicker::make('scheduled_at')
                    ->label(__('crm.fields.scheduled_at'))
                    ->required()
                    ->default(now()),
                DatePicker::make('completed_at')
                    ->label(__('crm.fields.completed_at')),
            ]);

        $components[] = Section::make(__('crm.sections.follow_up_content'))
            ->columnSpanFull()
            ->schema([
                Grid::make(1)->schema([
                    Textarea::make('offer_text')
                        ->label(__('crm.fields.offer_text'))
                        ->columnSpanFull(),
                    Textarea::make('customer_reply')
                        ->label(__('crm.fields.customer_reply'))
                        ->columnSpanFull(),
                    Textarea::make('internal_notes')
                        ->label(__('crm.fields.internal_notes'))
                        ->columnSpanFull(),
                    KeyValue::make('meta')
                        ->label(__('crm.fields.meta'))
                        ->columnSpanFull(),
                ]),
            ]);

        return $components;
    }

    protected static function statusActionIs(?int $statusId, FollowUpStatusAction $action): bool
    {
        if (! $statusId) {
            return false;
        }

        $status = FollowUpStatus::query()->find($statusId);

        return $status?->action === $action;
    }
}
