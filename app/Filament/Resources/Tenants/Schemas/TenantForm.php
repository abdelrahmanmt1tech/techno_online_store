<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Models\Plan;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Stancl\Tenancy\Database\Models\Domain;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.tenant_details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('dashboard.tenant_name'))
                            ->maxLength(255)
                            ->required(),

                        TextInput::make('email')
                            ->label(__('dashboard.email'))
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label(__('dashboard.phone'))
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('password')
                            ->label(__('dashboard.password'))
                            ->password()
                            ->revealable()
                            ->required(fn ($record) => $record === null)
                            ->dehydrated(fn ($state) => filled($state)),

                        TextInput::make('password_confirmation')
                            ->label(__('dashboard.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->required(fn ($record) => $record === null)
                            ->dehydrated(false),

                        TextInput::make('subdomain')
                            ->label(__('dashboard.subdomain'))
                            ->maxLength(63)
                            ->required()
                            ->regex('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/')
                            ->helperText(__('dashboard.subdomain_help'))
                            ->rules([
                                fn (TextInput $component): Closure => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                                    $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
                                    $fullDomain = $value.'.'.$centralDomain;

                                    $query = Domain::where('domain', $fullDomain);

                                    $record = $component->getRecord();
                                    if ($record) {
                                        $existingDomain = $record->domains()->first();
                                        if ($existingDomain) {
                                            $query->where('id', '!=', $existingDomain->id);
                                        }
                                    }

                                    if ($query->exists()) {
                                        $fail(__('dashboard.domain_taken'));
                                    }
                                },
                            ])
                            ->afterStateHydrated(function (TextInput $component, $state, $record): void {
                                if ($record) {
                                    $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
                                    $domain = $record->domains()->first()?->domain;
                                    if ($domain) {
                                        $component->state(str_replace('.'.$centralDomain, '', $domain));
                                    }
                                }
                            }),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.subscription_plan'))
                    ->columns(2)
                    ->schema([
                        Select::make('plan_id')
                            ->label(__('dashboard.plan'))
                            ->options(fn () => Plan::all()->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function ($state, $set) {
                                $plan = Plan::find($state);
                                if ($plan) {
                                    $set('price', $plan->price);
                                    $set('currency', $plan->currency);

                                    if ($plan->type === 'subscription' && $plan->subscription_period === 'monthly') {
                                        $set('expires_at', now()->addMonth());
                                    } elseif ($plan->type === 'subscription' && $plan->subscription_period === 'yearly') {
                                        $set('expires_at', now()->addYear());
                                    } elseif ($plan->type === 'commission') {
                                        $set('expires_at', null);
                                    }
                                }
                            }),

                        TextInput::make('price')
                            ->label(__('dashboard.price'))
                            ->numeric()
                            // ->prefix(fn (Get $get) => $get('currency') ?? 'SAR')
                            ->required()
                            ->dehydrated(),

                        Select::make('currency')
                            ->label(__('dashboard.currency'))
                            ->options([
                                'SAR' => 'SAR',
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                                'EGP' => 'EGP',
                                'AED' => 'AED',
                            ])
                            ->required()
                            ->native(false),

                        DateTimePicker::make('started_at')
                            ->label(__('dashboard.started_at'))
                            ->default(now())
                            ->required(),

                        DateTimePicker::make('expires_at')
                            ->label(__('dashboard.expires_at'))
                            ->nullable()
                            ->hidden(fn (Get $get) => Plan::find($get('plan_id'))?->type === 'commission'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
