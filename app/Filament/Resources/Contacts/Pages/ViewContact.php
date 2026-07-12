<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_as_read')
                ->label(__('dashboard.update_status'))
                ->color('primary')
                ->schema([
                    Select::make('status')
                        ->label(__('dashboard.new_status'))
                        ->options([
                            'pending' => __('dashboard.pending'),
                            'on_progress' => __('dashboard.on_progress'),
                            'completed' => __('dashboard.completed_status'),
                        ]),
                ])
                ->action(function (array $data) {
                    $this->record->update(['status' => $data['status']]);
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        if (! $record->read_at) {
            $record->update(['read_at' => now()]);
        }

        return $data;
    }
}
