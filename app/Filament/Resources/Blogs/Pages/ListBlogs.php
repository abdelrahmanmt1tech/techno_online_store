<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;

class ListBlogs extends ListRecords
{
    protected static string $resource = BlogResource::class;


       protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
