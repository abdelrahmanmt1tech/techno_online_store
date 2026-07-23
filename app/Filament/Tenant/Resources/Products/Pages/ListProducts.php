<?php

namespace App\Filament\Tenant\Resources\Products\Pages;

use App\Filament\Tenant\Resources\Products\ProductResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('seo')
                ->color('info')
                ->label(__('dashboard.page_header'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('title')
                                ->label(__('dashboard.title'))
                                ->default(fn () => Setting::where('key', 'products_title')->first()?->value),

                            TextInput::make('subtitle')
                                ->label(__('dashboard.subtitle'))
                                ->default(fn () => Setting::where('key', 'products_subtitle')->first()?->value),

                            TextInput::make('meta_title')
                                ->label(__('dashboard.meta_title'))
                                ->default(fn () => Setting::where('key', 'products_meta_title')->first()?->value),

                            Textarea::make('meta_description')
                                ->label(__('dashboard.meta_description'))
                                ->rows(3)
                                ->default(fn () => Setting::where('key', 'products_meta_description')->first()?->value),

                            TagsInput::make('keywords')
                                ->label(__('dashboard.keywords'))
                                ->placeholder(__('dashboard.keywords_placeholder'))
                                ->separator(' ')
                                ->default(function () {
                                    $v = Setting::where('key', 'products_keywords')->first()?->value;

                                    return $v !== null && $v !== '' ? explode(' ', $v) : [];
                                }),

                            TextInput::make('canonical_url')
                                ->label(__('dashboard.canonical_url'))
                                ->url()
                                ->nullable()
                                ->default(fn () => Setting::where('key', 'products_canonical_url')->first()?->value),

                            FileUpload::make('og_image')
                                ->label(__('dashboard.og_image'))
                                ->directory('seo')
                                ->image()
                                ->optimize('webp')
                                ->default(fn () => Setting::where('key', 'products_og_image')->first()?->value)
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data) {
                    foreach ($data as $key => $value) {
                        if ($key === 'keywords') {
                            Setting::updateOrCreate(
                                ['key' => 'products_'.$key],
                                ['value' => is_array($value) ? implode(' ', $value) : $value]
                            );
                        } else {
                            Setting::updateOrCreate(
                                ['key' => 'products_'.$key],
                                ['value' => $value]
                            );
                        }
                    }
                })
                ->successNotificationTitle(__('dashboard.saved_successfully')),

            CreateAction::make(),
        ];
    }
}
