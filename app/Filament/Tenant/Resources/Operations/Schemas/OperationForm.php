<?php

namespace App\Filament\Tenant\Resources\Operations\Schemas;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\AccountsCenter;
use App\Models\Tenant\Branch;
use App\Models\Tenant\FinancialPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OperationForm
{
    private static function recalculateTotals(Get $get, Set $set): void
    {
        $debit = collect($get('debitEntries') ?? [])
            ->sum(fn (array $row): float => (float) ($row['debit'] ?? 0));

        $credit = collect($get('creditEntries') ?? [])
            ->sum(fn (array $row): float => (float) ($row['credit'] ?? 0));

        $set('total_debit', $debit);
        $set('total_credit', $credit);
    }


    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([


                Section::make()
                    ->columns(2)
                    ->schema([

                        Repeater::make('debitEntries')->relationship()->label(__('dashboard.resources.operation.debit_side'))
                            ->addActionLabel(__('dashboard.resources.operation.add_debit'))->collapsible()->cloneable()->itemNumbers()->compact()
                            ->minItems(1)
                            ->rules([
                                fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $debit = collect($get('debitEntries') ?? [])
                                        ->sum(fn(array $row): float => (float)($row['debit'] ?? 0));

                                    $credit = collect($get('creditEntries') ?? [])
                                        ->sum(fn(array $row): float => (float)($row['credit'] ?? 0));

                                    if (round($debit, 2) !== round($credit, 2)) {
                                        $fail(__('dashboard.resources.operation.balance_validation'));
                                    }
                                },
                            ])
                            ->schema([
                                Select::make('account_tree_id')->label(__('dashboard.resources.operation.account'))
                                    ->required()->relationship(
                                        'accountTree',
                                        'account_name',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query
                                            ->where('is_disabled', false)
                                            ->where(function (Builder $q): Builder {
                                                return $q
                                                    ->whereNotNull('parent_id')
                                                    ->orWhereDoesntHave('subAccounts');
                                            })
                                    )
                                    ->preload()
                                    ->searchable()
                                ,
                                TextInput::make('debit')->label(__('dashboard.resources.operation.value'))->numeric()->required(),
                                Textarea::make('notes')->label(__('dashboard.resources.operation.notes'))->required()->columnSpanFull(),
                            ])
                            ->live(onBlur: true)
                            ->columns(2)
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                self::recalculateTotals($get, $set);
                            })
                        ,


                        Repeater::make('creditEntries')->relationship()->label(__('dashboard.resources.operation.credit_side'))
                            ->addActionLabel(__('dashboard.resources.operation.add_credit'))->collapsible()->cloneable()->itemNumbers()->compact()
                            ->minItems(1)
                            ->rules([
                                fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $debit = collect($get('debitEntries') ?? [])
                                        ->sum(fn(array $row): float => (float)($row['debit'] ?? 0));

                                    $credit = collect($get('creditEntries') ?? [])
                                        ->sum(fn(array $row): float => (float)($row['credit'] ?? 0));

                                    if (round($debit, 2) !== round($credit, 2)) {
                                        $fail(__('dashboard.resources.operation.balance_validation'));
                                    }
                                },
                            ])
                            ->schema([
                                Select::make('account_tree_id')->label(__('dashboard.resources.operation.account'))->required()
                                    ->relationship(
                                        'accountTree',
                                        'account_name',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query
                                            ->where('is_disabled', false)
                                            ->where(function (Builder $q): Builder {
                                                return $q
                                                    ->whereNotNull('parent_id')
                                                    ->orWhereDoesntHave('subAccounts');
                                            })
                                    )
                                    ->preload()
                                    ->searchable(),
                                TextInput::make('credit')->label(__('dashboard.resources.operation.value'))->numeric()->required(),
                                Textarea::make('notes')->label(__('dashboard.resources.operation.notes'))->required()->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                self::recalculateTotals($get, $set);
                            }),

                        TextEntry::make('total_debit')->inlineLabel()->badge()->label(__('dashboard.resources.operation.total_debit'))
                            ->color(fn($get) => $get("total_debit") == $get("total_credit") ? "success" : "danger")

                        ,
                        TextEntry::make('total_credit')->inlineLabel()->badge()->label(__('dashboard.resources.operation.total_credit'))
                            ->color(fn($get) => $get("total_debit") == $get("total_credit") ? "success" : "danger")

                        ,


                        /*
                                                TextInput::make('total_debit')

                                                    ->numeric()
                                                    ->disabled()
                                                    ->default(0),


                                                TextInput::make('total_credit')
                                                    ->label("اجمالي الدائن")
                                                    ->numeric()
                                                    ->disabled()
                                                    ->default(0)*/


                    ])
                    ->columnSpanFull(),


                Section::make()->columns(4)
                    ->schema([

                        DatePicker::make('date')->date()->default(now())->required(),
                        Select::make('financial_period_id')
                            ->label(__('dashboard.resources.operation.financial_period'))
                            ->options(fn (): array => FinancialPeriod::query()
                                ->orderByDesc('start_date')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('operation_type')
                            ->label(__('dashboard.resources.operation.operation_type'))
                            ->options(\App\Enums\OperationType::options())
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false),
                        Select::make('settlement')->label(__('dashboard.resources.operation.account_type'))
                            ->options([
                                '0' => __('dashboard.resources.operation.settlement_normal'),
                                '1' => __('dashboard.resources.operation.settlement_adjustment'),
                            ])
                            ->required(),

                        Hidden::make("linkable_type")->default(Branch::class),
                        /*
                          Select::make('linkable_type')
                              ->label('نوع الجهة')
                              ->options([
                                  Client::class => 'عميل',
                                  Supplier::class => 'مورد',
                                  Branch::class => 'فرع',
                                  Franchise::class => 'فرانشايز',
                              ])
                              ->searchable()
                              ->native(false)
                              ->live()
                          //   ->afterStateUpdated(fn($set) => $set('linkable_id', null))
                          ,*/


                        Select::make('linkable_id')
                            ->label(__('dashboard.resources.operation.entity'))
                            ->options(function (callable $get) {
                                return Branch::pluck('name', 'id')->toArray();

//                                return match ($type) {
//                                    Client::class => Client::pluck('name', 'id')->toArray(),
//                                    Supplier::class => Supplier::pluck('name', 'id')->toArray(),
//                                    Branch::class => Branch::pluck('name', 'id')->toArray(),
//                                    Franchise::class => Franchise::pluck('name', 'id')->toArray(),
//                                    default => [],
//                                };
                            })
                            ->preload()
                            ->searchable()
                            ->native(false)
                            ->placeholder(__('dashboard.resources.operation.entity_placeholder'))
//                            ->disabled(fn(callable $get) => !$get('linkable_type'))
                        ,

                        Textarea::make('comment')
                            ->label(__('dashboard.resources.operation.comment_label'))
                            ->columnSpan(1)
                            ->required(),


                    ])->columnSpanFull(),

                Section::make(__('dashboard.resources.operation.accounts_centers_section'))
                    ->description(__('dashboard.resources.operation.accounts_centers_section_desc'))
                    ->schema([
                        Repeater::make('accounts_center_entries')
                            ->label(__('dashboard.resources.operation.accounts_centers_entries'))
                            ->addActionLabel(__('dashboard.resources.operation.add_accounts_center_entry'))
                            ->defaultItems(0)
                            ->collapsible()
                            ->cloneable()
                            ->itemNumbers()
                            ->schema([
                                Select::make('accounts_center_id')
                                    ->label(__('dashboard.resources.operation.accounts_center'))
                                    ->options(fn (): array => AccountsCenter::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required(),
                                TextInput::make('debit')
                                    ->label(__('dashboard.resources.operation.debit'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('credit')
                                    ->label(__('dashboard.resources.operation.credit'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                Textarea::make('notes')
                                    ->label(__('dashboard.resources.operation.notes'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

            ]);

    }
}
