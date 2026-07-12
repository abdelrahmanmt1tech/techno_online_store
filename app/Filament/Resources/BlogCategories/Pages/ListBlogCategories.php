<?php

namespace App\Filament\Resources\BlogCategories\Pages;

use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;

class ListBlogCategories extends ListRecords
{
    protected static string $resource = BlogCategoryResource::class;

   protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    
}
