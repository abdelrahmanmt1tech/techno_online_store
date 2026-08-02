<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Schemas;

use App\Enums\PaymentMethod;
use App\Models\Tenant\Branch;
use App\Support\Crm\CrmBranchVisibility;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CommissionPaymentCycleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('crm.payment_cycles.sections.details'))
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextInput::make('cycle_number')
                        ->label(__('crm.payment_cycles.fields.cycle_number'))
                        ->disabled()
                        ->dehydrated(false),
                    DatePicker::make('period_from')
                        ->label(__('crm.payment_cycles.fields.period_from'))
                        ->native(false)
                        ->required(),
                    DatePicker::make('period_to')
                        ->label(__('crm.payment_cycles.fields.period_to'))
                        ->native(false)
                        ->required()
                        ->afterOrEqual('period_from'),
                    self::branchSelect(),
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
        ]);
    }

    private static function branchSelect(): Select
    {
        $user = Auth::user();

        $field = Select::make('branch_id')
            ->label(__('dashboard.fields.branch'))
            ->searchable()
            ->preload()
            ->placeholder('-');

        if ($user !== null && CrmBranchVisibility::canViewAllBranches($user)) {
            return $field->options(fn (): array => Branch::query()
                ->get()
                ->mapWithKeys(fn (Branch $branch): array => [$branch->id => $branch->name_translated])
                ->all());
        }

        $branchIds = $user !== null ? CrmBranchVisibility::branchIdsFor($user) : [];

        return $field
            ->options(fn (): array => Branch::query()
                ->whereIn('id', $branchIds)
                ->get()
                ->mapWithKeys(fn (Branch $branch): array => [$branch->id => $branch->name_translated])
                ->all())
            ->default(count($branchIds) === 1 ? $branchIds[0] : null)
            ->hidden(count($branchIds) <= 1)
            ->dehydrated();
    }
}
