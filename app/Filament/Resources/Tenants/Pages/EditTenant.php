<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterSave(): void
    {
        $subdomain = $this->form->getState()['subdomain'] ?? null;

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
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $domain = $this->record->domains()->first()?->domain;

        if ($domain) {
            $data['subdomain'] = str_replace('.'.$centralDomain, '', $domain);
        }

        return $data;
    }
}
