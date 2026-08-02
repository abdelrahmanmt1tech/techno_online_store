<?php

namespace App\Filament\Tenant\Resources\Clients\Pages;

use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Models\ClientIataCode;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;
//
//    protected function getHeaderActions(): array
//    {
//        return [
//            ViewAction::make(),
//            DeleteAction::make(),
//            ForceDeleteAction::make(),
//            RestoreAction::make(),
//        ];
//    }

    protected array $pendingIataCodesTags = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $data['iata_codes_tags'] = $this->record->iataCodes->pluck('iata_code')->toArray();

        return $data;
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

    protected function afterSave(): void
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
        $this->record->iataCodes()->delete();
        foreach ($codes as $code) {
            if ($code === '') {
                continue;
            }
            if (ClientIataCode::where('iata_code', $code)->where('client_id', '!=', $this->record->id)->exists()) {
                continue;
            }
            $this->record->iataCodes()->create(['iata_code' => $code]);
        }
    }


}
