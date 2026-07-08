<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Closure;
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
            ]);
    }
}
