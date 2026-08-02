<?php

namespace App\Filament\Crm\Resources\Opportunities\Schemas;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Campaign;
use App\Models\Tenant\OpportunityStage;
use App\Models\TenantUser;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class OpportunityForm
{
    public static function configure(Schema $schema, bool $lockClient = false): Schema
    {
        return $schema->components(self::components($lockClient));
    }

    /**
     * @return array<int, Component|Field>
     */
    public static function components(bool $lockClient = false): array
    {
        return [
            Section::make(__('crm.sections.details'))
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    $lockClient
                        ? Hidden::make('client_id')->required()
                        : Select::make('client_id')
                            ->label(__('crm.fields.client'))
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    Hidden::make('created_by'),
                    $lockClient
                        ? Select::make('opportunity_stage_id')
                            ->label(__('crm.fields.stage'))
                            ->options(OpportunityStage::query()->orderBy('sort_order')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                        : Select::make('opportunity_stage_id')
                            ->label(__('crm.fields.stage'))
                            ->relationship(
                                'opportunityStage',
                                'name',
                                fn ($query) => $query->orderBy('sort_order'),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                    TextInput::make('title')
                        ->label(__('crm.fields.title'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('amount')
                        ->label(__('crm.fields.amount'))
                        ->numeric(),
                    TextInput::make('agreed_amount')
                        ->label(__('crm.fields.agreed_amount'))
                        ->numeric(),
                    Textarea::make('description')
                        ->label(__('crm.fields.description'))
                        ->columnSpanFull(),

                    self::assignedToSelect($lockClient),

                    Hidden::make('first_assigned_to'),
                    self::branchSelect($lockClient),
                    $lockClient
                        ? Select::make('campaign_id')
                            ->label(__('crm.fields.campaign'))
                            ->options(Campaign::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                        : Select::make('campaign_id')
                            ->label(__('crm.fields.campaign'))
                            ->relationship('campaign', 'name')
                            ->searchable()
                            ->preload(),
                    KeyValue::make('meta')
                        ->label(__('crm.fields.meta'))
                        ->columnSpanFull(),
                ]),
        ];
    }

    private static function assignedToSelect(bool $lockClient): Select
    {
        $field = $lockClient
            ? Select::make('assigned_to')
                ->options(User::query()->pluck('name', 'id'))
            : Select::make('assigned_to')
                ->relationship('assignedTo', 'name');

        return $field
            ->label(__('crm.fields.assigned_to'))
            ->searchable()
            ->preload()
            ->default(fn (): ?int => Auth::id());
    }

    private static function branchSelect(bool $lockClient): Select
    {
        $field = $lockClient
            ? Select::make('branch_id')
                ->options(fn (): array => Branch::query()
                    // ->orderBy('code')
                    ->get()
                    ->mapWithKeys(fn (Branch $branch): array => [$branch->id => $branch->name_translated])
                    ->all())
            : Select::make('branch_id')
                ->relationship('branch', 'name');

        return $field
            ->label(__('dashboard.fields.branch'))
            ->searchable()
            ->preload()
            ->placeholder('-');
    }
}
