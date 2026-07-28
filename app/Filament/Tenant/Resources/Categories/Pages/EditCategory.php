<?php

namespace App\Filament\Tenant\Resources\Categories\Pages;

use App\Filament\Tenant\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn ($record) => $record->children()->exists())
                ->tooltip(fn ($record) => $record->children()->exists() ? __('dashboard.cannot_delete_category_has_children') : null)
                ->visible(fn () => Auth::user()->can('categories.delete')),
        ];
    }
}
