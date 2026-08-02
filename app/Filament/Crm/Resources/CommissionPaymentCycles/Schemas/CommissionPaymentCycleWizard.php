<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Schemas;

use App\Enums\PaymentMethod;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Pages\CreateCommissionPaymentCycle;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

final class CommissionPaymentCycleWizard
{
    /**
     * @return array<int, Step>
     */
    public static function steps(CreateCommissionPaymentCycle $page): array
    {
        $canPayPartial = Auth::user()?->can('crm_commission_payment_cycles.pay_partial') ?? false;

        return [
            Step::make(__('crm.payment_cycles.wizard.steps.period_scope'))
                ->icon(Heroicon::CalendarDays)
                ->description(__('crm.payment_cycles.wizard.descriptions.period_scope'))
                ->columns(['default' => 1, 'md' => 2])
                ->afterValidation(function () use ($page): void {
                    $page->loadPayableCommissions();

                    if ($page->payableCommissions === []) {
                        Notification::make()
                            ->title(__('crm.payment_cycles.wizard.no_payable_commissions'))
                            ->warning()
                            ->send();

                        throw new Halt;
                    }
                })
                ->schema([
                    DatePicker::make('period_from')
                        ->label(__('crm.payment_cycles.fields.period_from'))
                        ->native(false)
                        ->required(),
                    DatePicker::make('period_to')
                        ->label(__('crm.payment_cycles.fields.period_to'))
                        ->native(false)
                        ->required()
                        ->afterOrEqual('period_from'),
                    $page->branchSelectField(),
                    Select::make('employee_scope')
                        ->label(__('crm.payment_cycles.fields.employee_scope'))
                        ->options(fn (): array => $page->employeeScopeOptions())
                        ->required()
                        ->native(false)
                        ->live(),
                    Select::make('employee_id')
                        ->label(__('crm.commissions.fields.employee'))
                        ->options(fn (): array => $page->employeeOptions())
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => $get('employee_scope') === 'single')
                        ->visible(fn (Get $get): bool => $get('employee_scope') === 'single'),
                    Select::make('employee_ids')
                        ->label(__('crm.payment_cycles.fields.employees'))
                        ->options(fn (): array => $page->employeeOptions())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => $get('employee_scope') === 'multiple')
                        ->visible(fn (Get $get): bool => $get('employee_scope') === 'multiple'),
                ]),
            Step::make(__('crm.payment_cycles.wizard.steps.select_commissions'))
                ->icon(Heroicon::UserGroup)
                ->description(fn (): ?string => $page->payableCommissions === []
                    ? null
                    : __('crm.payment_cycles.wizard.commissions_found', [
                        'count' => count($page->payableCommissions),
                    ]))
                ->afterValidation(function () use ($page): void {
                    $page->initializeAllocations();
                })
                ->schema([
                    Placeholder::make('allocation_plan_note')
                        ->label('')
                        ->content(__('crm.payment_cycles.wizard.callouts.allocation_plan'))
                        ->columnSpanFull(),
                    Placeholder::make('no_commissions')
                        ->label('')
                        ->content(__('crm.payment_cycles.wizard.no_payable_commissions'))
                        ->visible(fn (): bool => $page->payableCommissions === []),
                    CheckboxList::make('selected_commission_ids')
                        ->label(__('crm.payment_cycles.fields.commissions'))
                        ->options(fn (): array => $page->commissionCheckboxOptions())
                        ->columns(1)
                        ->required()
                        ->bulkToggleable()
                        ->visible(fn (): bool => $page->payableCommissions !== []),
                ]),
            Step::make(__('crm.payment_cycles.wizard.steps.payment_amounts'))
                ->icon(Heroicon::Banknotes)
                ->description(__('crm.payment_cycles.wizard.descriptions.payment_amounts'))
                ->afterValidation(function () use ($page): void {
                    $page->validateStepThree();
                })
                ->schema([
                    Placeholder::make('allocation_vs_payment_note')
                        ->label('')
                        ->content(__('crm.payment_cycles.wizard.callouts.allocation_vs_payment'))
                        ->columnSpanFull(),
                    Repeater::make('allocations')
                        ->label('')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Hidden::make('opportunity_commission_id'),
                            Hidden::make('user_id'),
                            TextInput::make('employee_label')
                                ->label(__('crm.commissions.fields.employee'))
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('opportunity_label')
                                ->label(__('crm.fields.opportunity'))
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('remaining_amount')
                                ->label(__('crm.commissions.fields.remaining_amount'))
                                ->disabled()
                                ->dehydrated(),
                            Select::make('payment_mode')
                                ->label(__('crm.payment_cycles.fields.payment_mode'))
                                ->options([
                                    'full' => __('crm.payment_cycles.modes.full_payment'),
                                    'partial' => __('crm.payment_cycles.modes.partial_payment'),
                                ])
                                ->required()
                                ->native(false)
                                ->disabled(! $canPayPartial)
                                ->dehydrated()
                                ->when(
                                    $canPayPartial,
                                    fn (Select $field): Select => $field
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                            if ($state === 'full') {
                                                $set('planned_payment_amount', $get('remaining_amount'));
                                            }
                                        }),
                                ),
                            TextInput::make('planned_payment_amount')
                                ->label(__('crm.payment_cycles.fields.planned_payment_amount'))
                                ->numeric()
                                ->step(0.01)
                                ->required()
                                ->disabled(fn (Get $get): bool => ($get('payment_mode') ?? 'full') === 'full' || ! $canPayPartial)
                                ->dehydrated(),
                        ])
                        ->columns(['default' => 1, 'md' => 2, 'xl' => 3]),
                ]),
            Step::make(__('crm.payment_cycles.wizard.steps.payment_details'))
                ->icon(Heroicon::DocumentText)
                ->description(__('crm.payment_cycles.wizard.descriptions.payment_details'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Placeholder::make('execution_note')
                        ->label('')
                        ->content(__('crm.payment_cycles.wizard.callouts.execution_later'))
                        ->columnSpanFull(),
                    DatePicker::make('payment_date')
                        ->label(__('crm.payment_cycles.fields.payment_date'))
                        ->native(false),
                    Select::make('payment_method')
                        ->label(__('crm.payment_cycles.fields.payment_method'))
                        ->options(PaymentMethod::options())
                        ->native(false)
                        ->searchable(),
                    TextInput::make('reference_number')
                        ->label(__('crm.payment_cycles.fields.reference_number'))
                        ->maxLength(255),
                    Textarea::make('notes')
                        ->label(__('crm.fields.notes'))
                        ->columnSpanFull(),
                ]),
            Step::make(__('crm.payment_cycles.wizard.steps.review'))
                ->icon(Heroicon::CheckCircle)
                ->description(__('crm.payment_cycles.wizard.descriptions.review'))
                ->schema([
                    Placeholder::make('preview_summary')
                        ->label(__('crm.payment_cycles.wizard.preview_summary'))
                        ->content(fn (): string => $page->buildPreviewSummary()),
                    Checkbox::make('submit_for_approval')
                        ->label(__('crm.payment_cycles.wizard.submit_for_approval'))
                        ->helperText(__('crm.payment_cycles.wizard.submit_for_approval_help'))
                        ->visible(fn (): bool => Auth::user()?->can('crm_commission_payment_cycles.update') ?? false),
                ]),
        ];
    }
}
