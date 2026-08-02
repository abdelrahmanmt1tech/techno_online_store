<?php

namespace App\Filament\Tenant\Resources\Clients\Pages;

use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Models\ClientIataCode;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;
    protected array $pendingIataCodesTags = [];

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingIataCodesTags = $data['iata_codes_tags'] ?? [];
        if (! is_array($this->pendingIataCodesTags)) {
            $this->pendingIataCodesTags = [];
        }
        unset($data['iata_codes_tags']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncIataCodesFromTags();
    }

    private function syncIataCodesFromTags(): void
    {
        $tags = $this->pendingIataCodesTags;
        if (! is_array($tags)) {
            return;
        }
        $codes = array_values(array_unique(array_map(fn ($v) => strtoupper(trim((string) $v)), array_filter($tags))));
        foreach ($codes as $code) {
            if ($code === '') {
                continue;
            }
            if (ClientIataCode::where('iata_code', $code)->exists()) {
                continue;
            }
            $this->record->iataCodes()->create(['iata_code' => $code]);
        }
    }

}
