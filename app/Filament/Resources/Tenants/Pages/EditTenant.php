<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterSave(): void
    {
        $state = $this->form->getState();

        $subdomain = $state['subdomain'] ?? null;

        if ($subdomain) {
            $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
            $fullDomain = $subdomain.'.'.$centralDomain;
            $existingDomain = $this->record->domains()->first();

            if ($existingDomain) {
                if ($existingDomain->domain !== $fullDomain) {
                    $existingDomain->update(['domain' => $fullDomain]);
                }
            } else {
                $this->record->createDomain($fullDomain);
            }
        }

        if ($state['plan_id'] ?? null) {
            $subscription = $this->record->subscriptions()->first();
            $planData = [
                'plan_id' => $state['plan_id'],
                'price' => $state['price'] ?? 0,
                'currency' => $state['currency'] ?? 'SAR',
                'started_at' => $state['started_at'] ?? now(),
                'expires_at' => $state['expires_at'] ?? null,
                'status' => 'active',
            ];

            if ($subscription) {
                $subscription->update($planData);
            } else {
                $this->record->subscriptions()->create($planData);
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $domain = $this->record->domains()->first()?->domain;

        if ($domain) {
            $data['subdomain'] = str_replace('.'.$centralDomain, '', $domain);
        }

        $subscription = $this->record->subscriptions()->first();
        if ($subscription) {
            $data['plan_id'] = $subscription->plan_id;
            $data['price'] = $subscription->price;
            $data['currency'] = $subscription->currency;
            $data['started_at'] = $subscription->started_at;
            $data['expires_at'] = $subscription->expires_at;
        }

        return $data;
    }
}
