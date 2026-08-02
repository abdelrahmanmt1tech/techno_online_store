<?php

namespace App\Filament\Tenant\Resources\Operations\Tables;

use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Client;
use App\Models\Tenant\Entry;
use App\Filament\Tenant\Resources\Operations\OperationResource;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Franchise;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Tenant\Supplier;
use App\Models\Ticket;
use App\Services\Accounting\PaymentCommissionEntryDisplay;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\Auth;

class OperationsTable
{
    /**
     * @var array<int, string>
     */
    private static array $safesBankNameCache = [];


    /**
     * @param  (callable(): array{total_debit: float, total_credit: float})|null  $priorDebitCreditResolver
     */
    public static function configure(Table $table, ?callable $priorDebitCreditResolver = null): Table
    {
        return $table
            ->groups([
                Group::make('operation.id')
                    ->label(__('dashboard.resources.operation.operation_no'))
                    ->getDescriptionFromRecordUsing(function ($record): string {
                        $operation = $record->operation;
                        if (!$operation) {
                            return '-';
                        }

                        $comment = (string)($operation->comment ?? '-');

                        $linkable = $operation->linkable;
                        $linkableLabel = self::formatMorphLabel($operation->linkable_type);
                        $linkableName = self::formatMorphName($linkable);
                        $linkableText = trim($linkableLabel . ' ' . $linkableName);

                        $service = $operation->service;
                        $serviceLabel = self::formatMorphLabel($operation->service_type);
                        $serviceName = self::formatServiceName($service);
                        $serviceText = trim($serviceLabel . ' ' . $serviceName);

                        return $comment
                            . ' | ' . __('dashboard.resources.operation.entity') . ': ' . ($linkableText !== '' ? $linkableText : '-')
                            . ' | ' . __('dashboard.resources.operation.source') . ': ' . ($serviceText !== '' ? $serviceText : '-');
                    })
                    ->collapsible()
                ,


                Group::make('accountTree.account_name')
                    ->label(__('dashboard.resources.operation.account'))
                    ->collapsible()
                ,

            ])
//            ->defaultGroup('operation.id')

            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->searchable()
                ,

                TextColumn::make('day_date')
                    ->label(__('dashboard.resources.operation.date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('operation_id')
                    ->label(__('dashboard.resources.operation.operation_no'))
                    ->sortable()
                    ->wrap(false)
                    ->searchable(),

                TextColumn::make('operation.financialPeriod.name')
                    ->label(__('dashboard.resources.operation.financial_period'))
                    ->toggleable(),

                TextColumn::make('operation.operation_type')
                    ->label(__('dashboard.resources.operation.operation_type'))
                    ->badge()
                    ->formatStateUsing(fn($state) => $state?->label() ?? (string)$state)
                    ->toggleable(),

                TextColumn::make('operation_linkable')
                    ->label(__('dashboard.resources.operation.entity'))
                    ->wrap(false)
                    ->state(function (Entry $record): string {
                        $op = $record->operation;
                        if (!$op) {
                            return '-';
                        }
                        $label = self::formatMorphLabel($op->linkable_type);
                        $name = self::formatMorphName($op->linkable);
                        $text = trim($label . ' ' . $name);
                        return $text !== '' ? $text : '-';
                    })
                    /*                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = trim($search);
                        if ($search === '') {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($search): void {
                            $q->where('linkable_id', 'like', "%{$search}%")
                                ->orWhere('linkable_type', 'like', "%{$search}%")
                                ->orWhereHasMorph(
                                    'linkable',
                                    [
                                        Client::class,
                                        Supplier::class,
                                        Branch::class,
                                        Franchise::class,
                                        Ticket::class,
                                        Reservation::class,
                                        Payment::class,
                                    ],
                                    function (Builder $morphQuery) use ($search): void {
                                        $morphQuery->where(function (Builder $mq) use ($search): void {
                                            $mq->where('name', 'like', "%{$search}%")
                                                ->orWhere('company_name', 'like', "%{$search}%")
                                                ->orWhere('account_name', 'like', "%{$search}%")
                                                ->orWhere('ticket_number_core', 'like', "%{$search}%")
                                                ->orWhere('reservation_number', 'like', "%{$search}%");
                                        });
                                    }
                                );
                        });
                    })

                    */
                    ->badge(),

                TextColumn::make('operation_service')
                    ->label(__('dashboard.resources.operation.source'))->wrap(false)
                    ->state(function (Entry $record): string {
                        $op = $record->operation;
                        if (!$op) {
                            return '-';
                        }
                        $label = self::formatMorphLabel($op->service_type);
                        $name = self::formatServiceName($op->service);
                        $text = trim($label . ' ' . $name);
                        return $text !== '' ? $text : '-';
                    })
                    ->badge(),

                TextColumn::make('settlement_payment_method')
                    ->label(__('dashboard.resources.operation.settlement_payment_method'))
                    ->state(function (Entry $record): string {
                        $payment = self::paymentFromEntry($record);
                        if (! $payment) {
                            return '-';
                        }

                        return PaymentMethod::tryFrom((string) $payment->payment_method)?->label()
                            ?? (string) $payment->payment_method;
                    })
                    ->toggleable(),

                TextColumn::make('settlement_safes_bank')
                    ->label(__('dashboard.resources.operation.settlement_safes_bank'))
                    ->state(fn (Entry $record): string => self::paymentSafeName($record))
                    ->toggleable(),

//                TextColumn::make('settlement_breakdown')
//                    ->label(__('dashboard.resources.operation.settlement_breakdown'))
//                    ->state(fn (Entry $record): string => self::paymentSettlementBreakdown($record))
//                    ->toggleable(),

                TextColumn::make('accountTree.account_name')
                    ->label(__('dashboard.resources.operation.account'))
                    ->wrap(false)
                        //                    ->searchable()
                ,

                TextColumn::make('pnr_number')
                    ->label(__('dashboard.resources.operation.pnr_number'))
                    ->state(function (Entry $record): string {
                        $operation = $record->operation;
                        if (! $operation) {
                            return '-';
                        }

                        $service = $operation->service;
                        if (!$service) {
                            return '-';
                        }

                        // Check for ticket number first
                        if (property_exists($service, 'ticket_number_core') && !empty($service->ticket_number_core)) {
                            return (string)$service->ticket_number_core;
                        }

                        // Then check for reservation number
                        if (property_exists($service, 'reservation_number') && !empty($service->reservation_number)) {
                            return (string)$service->reservation_number;
                        }

                        return '-';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = trim($search);
                        if ($search === '') {
                            return $query;
                        }

                        return $query->whereHas('operation', function (Builder $opQuery) use ($search): void {
                            $opQuery->whereHasMorph(
                                'service',
                                [Ticket::class, Reservation::class],
                                function (Builder $serviceQuery, string $serviceType) use ($search): void {
                                    if ($serviceType === Ticket::class) {
                                        $serviceQuery->where('ticket_number_core', 'like', "%{$search}%");
                                        return;
                                    }

                                    if ($serviceType === Reservation::class) {
                                        $serviceQuery->where('reservation_number', 'like', "%{$search}%");
                                    }
                                }
                            );
                        });
                    })
                    ->toggleable(),

                TextColumn::make('entry_type')
                    ->label(__('dashboard.resources.operation.entry_type'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('branch.name')
                    ->label(__('dashboard.resources.operation.branch'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('debit')
                    ->label(__('dashboard.resources.operation.debit'))
                    // ->numeric()
                    ->color('danger')
                    ->sortable()
                    ->getStateUsing(function (Entry $record): string {
                        $d = PaymentCommissionEntryDisplay::displayDebitCredit($record)['debit'];

                        return $d !== null ? number_format($d, 2, '.', '') : '';
                    })
                    ->summarize(self::debitSummarizers($priorDebitCreditResolver))

                ,

                TextColumn::make('credit')
                    ->label(__('dashboard.resources.operation.credit'))
                    // ->numeric()
                    ->color('success')
                    ->sortable()
                    ->getStateUsing(function (Entry $record): string {
                        $c = PaymentCommissionEntryDisplay::displayDebitCredit($record)['credit'];

                        return $c !== null ? number_format($c, 2, '.', '') : '';
                    })
                    ->summarize(self::creditSummarizers($priorDebitCreditResolver))
                ,

                TextColumn::make('net')
                    ->label(__('dashboard.resources.operation.net'))
                    ->getStateUsing(function (Entry $record): float {
                        $a = PaymentCommissionEntryDisplay::displayDebitCredit($record);

                        return (float) ($a['debit'] ?? 0) - (float) ($a['credit'] ?? 0);
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 2, '.', ''))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $dir = strtolower($direction) === 'desc' ? 'desc' : 'asc';
                        $table = $query->getModel()->getTable();

                        return $query->orderByRaw("(COALESCE({$table}.debit,0) - COALESCE({$table}.credit,0)) {$dir}");
                    })
                    ->summarize(self::netSummarizers($priorDebitCreditResolver)),

                TextColumn::make('notes')->searchable()
                    ->label(__('dashboard.resources.operation.notes'))
                    ->limit(30)->tooltip(fn($state) => $state)->wrap(false)->wrapHeader(false),

                TextColumn::make('operation.comment')->searchable()
                    ->label(__('dashboard.resources.operation.operation_comment'))
                    ->limit(30)->tooltip(fn($state) => $state)->wrap(false)->wrapHeader(false)
                ,

                TextColumn::make('operation.total_debit')
                    ->label(__('dashboard.resources.operation.total_debit'))
                    // ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('operation.total_credit')
                    ->label(__('dashboard.resources.operation.total_credit'))
                    // ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('operation.posted_at')
                    ->label(__('dashboard.resources.operation.posted_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('operation.is_posted')
                    ->label(__('dashboard.resources.operation.is_posted'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('operation.is_locked')
                    ->label(__('dashboard.resources.operation.is_locked'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('operation.is_system_generated')
                    ->label(__('dashboard.resources.operation.is_system_generated'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('operation.source_operation_id')
                    ->label(__('dashboard.resources.operation.source_operation'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label(__('dashboard.resources.operation.period'))
                    ->schema([
                        DatePicker::make('from')->label(__('dashboard.resources.operation.from')),
                        DatePicker::make('to')->label(__('dashboard.resources.operation.to')),
                    ])
                    ->columns(2)->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $q, $date): Builder => $q->whereDate('day_date', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn(Builder $q, $date): Builder => $q->whereDate('day_date', '<=', $date),
                            );
                    }),

                Filter::make('entity')
                    ->label(__('dashboard.resources.operation.entity'))
                    ->schema([
                        Select::make('linkable_type')
                            ->label(__('dashboard.resources.operation.entity_type'))
                            ->options([
                                Client::class => __('dashboard.resources.operation.morph_client'),
                                Supplier::class => __('dashboard.resources.operation.morph_supplier'),
                                Branch::class => __('dashboard.resources.operation.morph_branch'),
                                Franchise::class => __('dashboard.resources.operation.morph_franchise'),
                            ])
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn(callable $set) => $set('linkable_id', null)),
                        Select::make('linkable_id')
                            ->label(__('dashboard.resources.operation.entity'))
                            ->options(function (callable $get): array {
                                $type = $get('linkable_type');
                                if (!$type) {
                                    return [];
                                }

                                return match ($type) {
                                    Client::class => Client::pluck('name', 'id')->filter()->toArray(),
                                    Supplier::class => Supplier::pluck('name', 'id')->filter()->toArray(),
                                    Branch::class => Branch::pluck('name', 'id')->filter()->toArray(),
                                    Franchise::class => Franchise::pluck('name', 'id')->filter()->toArray(),
                                    default => [],
                                };
                            })
                            //                            ->searchable()
                            ->native(false)
                            ->disabled(fn(callable $get) => !$get('linkable_type')),
                        TextInput::make('linkable_name')
                            ->label(__('dashboard.resources.operation.entity_name')),
                    ])
                    ->columns(3)->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        $type = $data['linkable_type'] ?? null;
                        $id = $data['linkable_id'] ?? null;
                        $name = trim((string)($data['linkable_name'] ?? ''));

                        $query
                            ->when($type, fn(Builder $q): Builder => $q->where('linkable_type', $type))
                            ->when($id, fn(Builder $q): Builder => $q->where('linkable_id', $id));

                        if ($name !== '') {
                            $types = $type ? [$type] : [
                                Client::class,
                                Supplier::class,
                                Branch::class,
                                Franchise::class,
                            ];

                            $query->whereHasMorph('linkable', $types, function (Builder $morphQuery) use ($name): void {
                                $morphQuery->where(function (Builder $mq) use ($name): void {
                                    $mq->where('name', 'like', "%{$name}%")
                                        ->orWhere('company_name', 'like', "%{$name}%")
                                        ->orWhere('account_name', 'like', "%{$name}%")
                                        ->orWhere('ticket_number_core', 'like', "%{$name}%")
                                        ->orWhere('reservation_number', 'like', "%{$name}%");
                                });
                            });
                        }

                        return $query;
                    }),

                SelectFilter::make('account_tree_id')
                    ->label(__('dashboard.resources.operation.account'))
                    ->relationship('accountTree', 'account_name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Filter::make('financial_period')
                    ->label(__('dashboard.resources.operation.financial_period'))
                    ->schema([
                        Select::make('financial_period_id')
                            ->label(__('dashboard.resources.operation.financial_period'))
                            ->options(fn(): array => FinancialPeriod::query()
                                ->orderByDesc('start_date')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->native(false),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query->when(
                        $data['financial_period_id'] ?? null,
                        fn(Builder $q, $id): Builder => $q->whereHas('operation', fn(Builder $op) => $op->where('financial_period_id', $id))
                    )),

                Filter::make('entry_meta')
                    ->label(__('dashboard.resources.operation.entry_meta'))
                    ->schema([
                        TextInput::make('source_operation_id')
                            ->label(__('dashboard.resources.operation.source_operation')),
                        Select::make('entry_type')
                            ->label(__('dashboard.resources.operation.entry_type'))
                            ->options([
                                'opening' => __('dashboard.financial_periods.operation_types.opening'),
                                'normal' => __('dashboard.financial_periods.operation_types.normal'),
                                'adjustment' => __('dashboard.financial_periods.operation_types.adjustment'),
                                'carry_forward' => __('dashboard.financial_periods.operation_types.carry_forward'),
                                'reversal' => __('dashboard.financial_periods.operation_types.reversal'),
                            ])
                            ->native(false),
                        Select::make('branch_id')
                            ->label(__('dashboard.resources.operation.branch'))
                            ->options(fn(): array => Branch::query()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->native(false),
                    ])
                    ->columns(3)->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['entry_type'] ?? null, fn(Builder $q, $value): Builder => $q->where('entry_type', $value))
                            ->when($data['branch_id'] ?? null, fn(Builder $q, $value): Builder => $q->where('branch_id', $value))
                            ->when(
                                $data['source_operation_id'] ?? null,
                                fn(Builder $q, $value): Builder => $q->whereHas('operation', fn(Builder $op) => $op->where('source_operation_id', $value))
                            );
                    }),

                Filter::make('operation_meta')
                    ->label(__('dashboard.resources.operation.operation_no'))
                    ->schema([
                        TextInput::make('operation_id')
                            ->label(__('dashboard.resources.operation.operation_no')),
                        TextInput::make('operation_comment')
                            ->label(__('dashboard.resources.operation.operation_comment')),
                        Select::make('settlement')
                            ->label(__('dashboard.resources.operation.account_type'))
                            ->options([
                                '0' => __('dashboard.resources.operation.settlement_normal'),
                                '1' => __('dashboard.resources.operation.settlement_adjustment'),
                            ])
                            ->native(false),
                        Select::make('status')
                            ->label(__('dashboard.resources.operation.status'))
                            ->options([
                                '1' => __('dashboard.resources.operation.status_active'),
                                '0' => __('dashboard.resources.operation.status_inactive'),
                            ])
                            ->native(false),
                        Select::make('operation_type')
                            ->label(__('dashboard.resources.operation.operation_type'))
                            ->options(\App\Enums\OperationType::options())
                            ->native(false),
                        Select::make('is_posted')
                            ->label(__('dashboard.resources.operation.is_posted'))
                            ->options([
                                '1' => __('dashboard.resources.operation.yes'),
                                '0' => __('dashboard.resources.operation.no'),
                            ])
                            ->native(false),
                        Select::make('is_locked')
                            ->label(__('dashboard.resources.operation.is_locked'))
                            ->options([
                                '1' => __('dashboard.resources.operation.yes'),
                                '0' => __('dashboard.resources.operation.no'),
                            ])
                            ->native(false),
                        Select::make('is_system_generated')
                            ->label(__('dashboard.resources.operation.is_system_generated'))
                            ->options([
                                '1' => __('dashboard.resources.operation.yes'),
                                '0' => __('dashboard.resources.operation.no'),
                            ])
                            ->native(false),
                        Select::make('safes_bank_id')
                            ->label(__('dashboard.resources.operation.settlement_safes_bank'))
                            ->options(fn (): array => \App\Models\SafesBank::query()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->native(false),
                        Select::make('commission_case')
                            ->label(__('dashboard.resources.operation.settlement_commission_case'))
                            ->options([
                                'with_commission' => __('dashboard.resources.operation.settlement_with_commission'),
                                'without_commission' => __('dashboard.resources.operation.settlement_without_commission'),
                                'commission_entry' => __('dashboard.resources.operation.settlement_commission_entry_only'),
                            ])
                            ->native(false),
                        TextInput::make('entry_notes')
                            ->label(__('dashboard.resources.operation.notes')),
                    ])
                    ->columns(4)->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        $query->when(
                            $data['operation_id'] ?? null,
                            fn(Builder $q, $id): Builder => $q->where('operation_id', $id),
                        );

                        $entryNotes = trim((string)($data['entry_notes'] ?? ''));
                        if ($entryNotes !== '') {
                            $query->where('notes', 'like', "%{$entryNotes}%");
                        }

                        $comment = trim((string)($data['operation_comment'] ?? ''));
                        $settlement = $data['settlement'] ?? null;
                        $status = $data['status'] ?? null;
                        $operationType = $data['operation_type'] ?? null;
                        $isPosted = $data['is_posted'] ?? null;
                        $isLocked = $data['is_locked'] ?? null;
                        $isSystemGenerated = $data['is_system_generated'] ?? null;
                        $safesBankId = $data['safes_bank_id'] ?? null;
                        $commissionCase = $data['commission_case'] ?? null;
                        $commissionAccountId = (int) (\App\Models\Setting::query()->where('key', 'safes_banks_commissions_account_tree_id')->value('value') ?? 0);

                        if (
                            $comment !== ''
                            || ($settlement !== null && $settlement !== '')
                            || ($status !== null && $status !== '')
                            || ($operationType !== null && $operationType !== '')
                            || ($isPosted !== null && $isPosted !== '')
                            || ($isLocked !== null && $isLocked !== '')
                            || ($isSystemGenerated !== null && $isSystemGenerated !== '')
                            || ($safesBankId !== null && $safesBankId !== '')
                            || ($commissionCase !== null && $commissionCase !== '')
                        ) {
                            $query->whereHas('operation', function (Builder $opQuery) use ($comment, $settlement, $status, $operationType, $isPosted, $isLocked, $isSystemGenerated, $safesBankId, $commissionCase): void {
                                if ($comment !== '') {
                                    $opQuery->where('comment', 'like', "%{$comment}%");
                                }
                                if ($settlement !== null && $settlement !== '') {
                                    $opQuery->where('settlement', (bool)(int)$settlement);
                                }
                                if ($status !== null && $status !== '') {
                                    $opQuery->where('status', (bool)(int)$status);
                                }
                                if ($operationType !== null && $operationType !== '') {
                                    $opQuery->where('operation_type', $operationType);
                                }
                                if ($isPosted !== null && $isPosted !== '') {
                                    $opQuery->where('is_posted', (bool)(int)$isPosted);
                                }
                                if ($isLocked !== null && $isLocked !== '') {
                                    $opQuery->where('is_locked', (bool)(int)$isLocked);
                                }
                                if ($isSystemGenerated !== null && $isSystemGenerated !== '') {
                                    $opQuery->where('is_system_generated', (bool)(int)$isSystemGenerated);
                                }

                                $opQuery->when(
                                    ($safesBankId !== null && $safesBankId !== ''),
                                    fn (Builder $q): Builder => $q
                                        ->where('service_type', Payment::class)
                                        ->whereHasMorph('service', [Payment::class], fn (Builder $paymentQuery) => $paymentQuery->where('safes_bank_id', (int) $safesBankId))
                                );

                                if ($commissionCase === 'with_commission') {
                                    $opQuery->where('service_type', Payment::class)
                                        ->whereHasMorph('service', [Payment::class], fn (Builder $paymentQuery) => $paymentQuery->where('commission_amount', '>', 0));
                                } elseif ($commissionCase === 'without_commission') {
                                    $opQuery->where('service_type', Payment::class)
                                        ->whereHasMorph('service', [Payment::class], fn (Builder $paymentQuery) => $paymentQuery->where('commission_amount', '<=', 0));
                                }
                            });

                            if ($commissionCase === 'commission_entry' && $commissionAccountId > 0) {
                                $query->where('account_tree_id', $commissionAccountId)
                                    ->whereHas('operation', fn (Builder $q) => $q->where('service_type', Payment::class));
                            }
                        }

                        return $query;
                    }),

                Filter::make('side_amount')
                    ->label(__('dashboard.resources.operation.side_amount'))
                    ->schema([
                        Select::make('side')
                            ->label(__('dashboard.resources.operation.side_type'))
                            ->options([
                                'debit' => __('dashboard.resources.operation.debit'),
                                'credit' => __('dashboard.resources.operation.credit'),
                            ])
                            ->native(false),
                        TextInput::make('min')
                            ->label(__('dashboard.resources.operation.from')),
                        TextInput::make('max')
                            ->label(__('dashboard.resources.operation.to')),
                    ])
                    ->columns(3)->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        $side = $data['side'] ?? null;
                        $min = $data['min'] ?? null;
                        $max = $data['max'] ?? null;

                        if ($side === 'debit') {
                            $query->whereNotNull('debit');
                        } elseif ($side === 'credit') {
                            $query->whereNotNull('credit');
                        }

                        if ($min !== null && $min !== '') {
                            $query->where(function (Builder $q) use ($min): void {
                                $q->where('debit', '>=', $min)
                                    ->orWhere('credit', '>=', $min);
                            });
                        }

                        if ($max !== null && $max !== '') {
                            $query->where(function (Builder $q) use ($max): void {
                                $q->where('debit', '<=', $max)
                                    ->orWhere('credit', '<=', $max);
                            });
                        }

                        return $query;
                    }),

                TrashedFilter::make(),
            ], FiltersLayout::Modal)
            ->filtersFormWidth(Width::FourExtraLarge)
            ->recordActions([
                ViewAction::make()
                    ->url(fn(Entry $record): string => OperationResource::getUrl('view', ['record' => $record->operation_id]))
                    ->authorize(fn(): bool => Auth::user()?->can('operations.show') ?? false),

                EditAction::make()
                    ->url(fn(Entry $record): string => OperationResource::getUrl('edit', ['record' => $record->operation_id]))
                    // [ADDED] إخفاء منع تعديل القيد الافتتاحي من قائمة العمليات.
                    ->visible(fn (Entry $record): bool => ($record->operation?->operation_type?->value ?? (string) $record->operation?->operation_type) !== OperationType::OPENING->value)
                    ->authorize(fn(Entry $record): bool => (Auth::user()?->can('operations.update') ?? false)
                        && (($record->operation?->operation_type?->value ?? (string) $record->operation?->operation_type) !== OperationType::OPENING->value)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn(): bool => Auth::user()?->can('operations.delete_bulk') ?? false),
                    ForceDeleteBulkAction::make()
                        ->authorize(fn(): bool => Auth::user()?->can('operations.force_delete_bulk') ?? false),
                    RestoreBulkAction::make()
                        ->authorize(fn(): bool => Auth::user()?->can('operations.restore_bulk') ?? false),
                ]),
            ])
            ->defaultSort('day_date', 'desc')

            ;
    }

    /**
     * @param  (callable(): array{total_debit: float, total_credit: float})|null  $priorDebitCreditResolver
     * @return array<int, Sum|Summarizer>
     */
    private static function debitSummarizers(?callable $priorDebitCreditResolver): array
    {
        if ($priorDebitCreditResolver === null) {
            return [
                Sum::make()
                    ->formatStateUsing(fn ($state) => number_format($state, 2, '.', '')),
            ];
        }

        return [
            Summarizer::make()
                ->label(__('dashboard.pages.account_statement.sum_debit'))
                ->using(function ($query) use ($priorDebitCreditResolver): float {
                    $prior = $priorDebitCreditResolver();

                    return (float) $query->sum('debit') + (float) ($prior['total_debit'] ?? 0);
                })
                ->formatStateUsing(fn ($state) => number_format($state, 2, '.', '')),
        ];
    }

    /**
     * @param  (callable(): array{total_debit: float, total_credit: float})|null  $priorDebitCreditResolver
     * @return array<int, Sum|Summarizer>
     */
    private static function creditSummarizers(?callable $priorDebitCreditResolver): array
    {
        if ($priorDebitCreditResolver === null) {
            return [
                Sum::make()
                    ->formatStateUsing(fn ($state) => number_format($state, 2, '.', '')),
            ];
        }

        return [
            Summarizer::make()
                ->label(__('dashboard.pages.account_statement.sum_credit'))
                ->using(function ($query) use ($priorDebitCreditResolver): float {
                    $prior = $priorDebitCreditResolver();

                    return (float) $query->sum('credit') + (float) ($prior['total_credit'] ?? 0);
                })
                ->formatStateUsing(fn ($state) => number_format($state, 2, '.', '')),
        ];
    }

    /**
     * @param  (callable(): array{total_debit: float, total_credit: float})|null  $priorDebitCreditResolver
     * @return array<int, Summarizer>
     */
    private static function netSummarizers(?callable $priorDebitCreditResolver): array
    {
        if ($priorDebitCreditResolver === null) {
            return [
                Summarizer::make()
                    ->label(__('dashboard.resources.operation.sum_net'))
                    ->using(fn ($query): float => (float) $query->sum('debit') - (float) $query->sum('credit'))
                    ->formatStateUsing(fn ($state) => number_format($state, 2, '.', '')),
            ];
        }

        return [
            Summarizer::make()
                ->label(__('dashboard.pages.account_statement.sum_balance'))
                ->using(function ($query) use ($priorDebitCreditResolver): float {
                    $prior = $priorDebitCreditResolver();
                    $totalDebit = (float) $query->sum('debit') + (float) ($prior['total_debit'] ?? 0);
                    $totalCredit = (float) $query->sum('credit') + (float) ($prior['total_credit'] ?? 0);

                    return round($totalDebit - $totalCredit, 2);
                })
                ->formatStateUsing(fn ($state) => number_format($state, 2, '.', '')),
        ];
    }

    private static function formatMorphLabel(?string $fqcn): string
    {
        if (!$fqcn) {
            return '';
        }

        return match ($fqcn) {
            \App\Models\Client::class => __('dashboard.resources.operation.morph_client'),
            \App\Models\Supplier::class => __('dashboard.resources.operation.morph_supplier'),
            \App\Models\Branch::class => __('dashboard.resources.operation.morph_branch'),
            \App\Models\Franchise::class => __('dashboard.resources.operation.morph_franchise'),
            \App\Models\Ticket::class => __('dashboard.resources.operation.morph_ticket'),
            \App\Models\Reservation::class => __('dashboard.resources.operation.morph_reservation'),
            \App\Models\Payment::class => __('dashboard.resources.operation.morph_payment'),
            default => class_basename($fqcn),
        };
    }

    private static function formatMorphName(?Model $model): string
    {
        if (!$model) {
            return '';
        }

        return (string)(
            $model->name
            ?? $model->account_name
            ?? $model->company_name
            ?? $model->ticket_number_core
            ?? $model->reservation_number
            ?? $model->id
        );
    }

    private static function formatServiceName(?Model $service): string
    {
        // Prefer business identifiers for known service models.
        if (!$service) {
            return '';
        }

        if (property_exists($service, 'ticket_number_core') && !empty($service->ticket_number_core)) {
            return (string)$service->ticket_number_core;
        }

        if (property_exists($service, 'reservation_number') && !empty($service->reservation_number)) {
            return (string)$service->reservation_number;
        }

        return (string)($service->id ?? '');
    }

    private static function paymentFromEntry(Entry $record): ?Payment
    {
        $op = $record->operation;
        if (! $op || $op->service_type !== Payment::class) {
            return null;
        }

        $service = $op->service;
        return $service instanceof Payment ? $service : null;
    }

    private static function paymentSafeName(Entry $record): string
    {
        $payment = self::paymentFromEntry($record);
        if (! $payment || ! $payment->safes_bank_id) {
            return '-';
        }

        $safeId = (int) $payment->safes_bank_id;
        if (isset(self::$safesBankNameCache[$safeId])) {
            return self::$safesBankNameCache[$safeId];
        }

        $name = (string) (\App\Models\SafesBank::query()->whereKey($safeId)->value('name') ?? '-');
        self::$safesBankNameCache[$safeId] = $name !== '' ? $name : '-';

        return self::$safesBankNameCache[$safeId];
    }

    private static function paymentSettlementBreakdown(Entry $record): string
    {
        return PaymentCommissionEntryDisplay::settlementBreakdownForEntry($record);
    }
}
