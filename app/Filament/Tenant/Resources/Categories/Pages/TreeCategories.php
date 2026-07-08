<?php

namespace App\Filament\Tenant\Resources\Categories\Pages;

use App\Filament\Tenant\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Openplain\FilamentTreeView\Fields\TextField;
use Openplain\FilamentTreeView\Resources\Pages\TreePage;
use Openplain\FilamentTreeView\Tree;

class TreeCategories extends TreePage
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function tree(Tree $tree): Tree
    {
        return $tree
            ->maxDepth(10)
            ->fields([
                TextField::make('name')
                    ->weight(FontWeight::Medium)
                    ->dimWhenInactive(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn ($record) => $record->children()->exists())
                    ->tooltip(fn ($record) => $record->children()->exists() ? __('dashboard.cannot_delete_category_has_children') : null),
            ]);
    }
}
