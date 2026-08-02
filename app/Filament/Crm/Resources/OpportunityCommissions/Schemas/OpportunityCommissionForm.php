<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\Schemas;

use App\Enums\Crm\CommissionType;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Services\Crm\Commission\CommissionCalculator;
use App\Support\Crm\Commission\OpportunityCommissionAccess;
use App\Support\Crm\Commission\OpportunityCommissionState;
use App\Support\Money\DecimalMath;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class OpportunityCommissionForm
{
    public static function configure(Schema $schema, ?Opportunity $lockedOpportunity = null): Schema
    {
        return $schema->components(self::components($lockedOpportunity));
    }

    /**
     * @return array<int, Component>
     */
    public static function components(?Opportunity $lockedOpportunity = null): array
    {
        $user = Auth::user();

        return [
            Section::make(__('crm.commissions.sections.details'))
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    $lockedOpportunity
                        ? Hidden::make('opportunity_id')->default($lockedOpportunity->id)
                        : Select::make('opportunity_id')
                            ->label(__('crm.fields.opportunity'))
                            ->relationship('opportunity', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (! $state) {
                                    return;
                                }

                                $opportunity = Opportunity::query()->find($state);

                                if (! $opportunity) {
                                    return;
                                }

                                $set('base_amount', CommissionCalculator::defaultBaseAmount($opportunity));
                            }),

                    Select::make('user_id')
                        ->label(__('crm.commissions.fields.employee'))
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('commission_type')
                        ->label(__('crm.commissions.fields.commission_type'))
                        ->options(CommissionType::options())
                        ->disableOptionWhen(fn (string $value): bool => $value === CommissionType::ADJUSTMENT->value)
                        ->required(),

                    TextInput::make('base_amount')
                        ->label(__('crm.commissions.fields.base_amount'))
                        ->required()
                        ->default(fn (): string => $lockedOpportunity
                            ? CommissionCalculator::defaultBaseAmount($lockedOpportunity)
                            : DecimalMath::zero())
                        ->disabled(fn ($record): bool => $record !== null
                            && ! OpportunityCommissionState::isDirectlyEditable($record))
                        ->dehydrated()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                            if ($state === null || $state === '') {
                                return;
                            }

                            if (DecimalMath::isZero($state)) {
                                return;
                            }

                            $percentage = $get('commission_percentage');

                            if ($percentage === null || $percentage === '') {
                                return;
                            }

                            $synced = CommissionCalculator::syncFromPercentage($state, $percentage);
                            $set('commission_amount', $synced['commission_amount']);
                            $set('last_manual_edit_field', 'percentage');
                        })
                        ->rules(['required']),

                    TextInput::make('commission_percentage')
                        ->label(__('crm.commissions.fields.commission_percentage'))
                        ->required()
                        ->live(onBlur: true)
                        ->disabled(fn ($record): bool => $record !== null
                            && ! OpportunityCommissionState::isDirectlyEditable($record))
                        ->dehydrated()
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                            if ($state === null || $state === '') {
                                return;
                            }

                            $base = $get('base_amount') ?? DecimalMath::zero();

                            if (DecimalMath::isZero($base)) {
                                return;
                            }

                            $synced = CommissionCalculator::syncFromPercentage($base, $state);
                            $set('commission_amount', $synced['commission_amount']);
                            $set('last_manual_edit_field', 'percentage');
                        }),

                    TextInput::make('commission_amount')
                        ->label(__('crm.commissions.fields.commission_amount'))
                        ->required()
                        ->live(onBlur: true)
                        ->disabled(fn ($record): bool => $record !== null
                            && ! OpportunityCommissionState::isDirectlyEditable($record))
                        ->dehydrated()
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                            if ($state === null || $state === '') {
                                return;
                            }

                            $base = $get('base_amount') ?? DecimalMath::zero();

                            if (DecimalMath::isZero($base)) {
                                return;
                            }

                            $synced = CommissionCalculator::syncFromAmount($base, $state);
                            $set('commission_percentage', $synced['commission_percentage']);
                            $set('last_manual_edit_field', 'amount');
                        }),

                    DatePicker::make('due_at')
                        ->label(__('crm.commissions.fields.due_at'))
                        ->native(false),

                    Textarea::make('notes')
                        ->label(__('crm.fields.notes'))
                        ->columnSpanFull(),

                    Hidden::make('last_manual_edit_field'),
                    Hidden::make('status')->default('draft'),
                ]),
        ];
    }

    public static function canEditBaseAmount($record): bool
    {
        $user = Auth::user();

        return $user !== null
            && $record instanceof OpportunityCommission
            && OpportunityCommissionAccess::canChangeBaseAmount($user, $record);
    }
}
