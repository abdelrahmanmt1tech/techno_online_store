<?php

namespace App\Filament\Tenant\Resources\Orders\Pages;

use App\Filament\Tenant\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('updateStatus')
                ->label(__('dashboard.update_status'))
                ->color('primary')
                ->icon('heroicon-o-arrow-path')
                ->schema([
                    Select::make('status')
                        ->label(__('dashboard.status'))
                        ->options([
                            'pending' => __('dashboard.pending'),
                            'confirmed' => __('dashboard.confirmed'),
                            'processing' => __('dashboard.processing'),
                            'shipped' => __('dashboard.shipped'),
                            'delivered' => __('dashboard.delivered'),
                            'cancelled' => __('dashboard.cancelled'),
                            'returned' => __('dashboard.returned'),
                        ])
                        ->default(fn ($record) => $record->status)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update(['status' => $data['status']]);
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
