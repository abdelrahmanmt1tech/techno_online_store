<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Models\Country;
use App\Models\Currency;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Stancl\Tenancy\Database\Models\Domain;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.tenant_details'))
                    ->columns(3)
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

                        Select::make('country_id')
                            ->label(__('dashboard.country'))
                            ->options(fn () => Country::where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn ($c) => [
                                    $c->id => $c->getTranslation('name', app()->getLocale()),
                                ]))
                            ->searchable()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function ($state, $set) {
                                $country = Country::find($state);
                                if ($country) {
                                    $currency = Currency::where('name->en', $country->getTranslation('currency_name', 'en'))->first();
                                    if ($currency) {
                                        $set('currency_id', $currency->id);
                                    }
                                }
                            })
                            ->required(),

                        Select::make('currency_id')
                            ->label(__('dashboard.currency'))
                            ->options(fn () => Currency::where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn ($c) => [
                                    $c->id => $c->getTranslation('name', app()->getLocale()).' ('.$c->code.')',
                                ]))
                            ->searchable()
                            ->native(false)
                            ->required(),
                        TextInput::make('subdomain')
                            ->label(__('dashboard.subdomain'))
                            ->maxLength(63)
                            ->required()
                            ->regex('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/')
                            ->helperText(__('dashboard.subdomain_help'))
                            ->rules([
                                fn (TextInput $component): Closure => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                                    $centralDomain = parse_url(config('app.domain_url'), PHP_URL_HOST) ?? 'localhost';
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
                                    $centralDomain = parse_url(config('app.domain_url'), PHP_URL_HOST) ?? 'localhost';
                                    $domain = $record->domains()->first()?->domain;
                                    if ($domain) {
                                        $suffix = '.'.$centralDomain;
                                        $component->state(str_ends_with($domain, $suffix)
                                            ? substr($domain, 0, -strlen($suffix))
                                            : strtok($domain, '.'));
                                    }
                                }
                            }),

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

                    ])
                    ->columnSpanFull(),
            ]);
    }
}
