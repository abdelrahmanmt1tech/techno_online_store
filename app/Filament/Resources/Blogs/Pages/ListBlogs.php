<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Auth;

class ListBlogs extends ListRecords
{
    protected static string $resource = BlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('seo')
                ->color('info')
                ->label(__('dashboard.seo_settings'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('meta_title_ar')
                                ->label(__('dashboard.meta_title_ar'))
                                ->default(fn () => Setting::where('key', 'blogs_meta_title_ar')->first()?->value),

                            TextInput::make('meta_title_en')
                                ->label(__('dashboard.meta_title_en'))
                                ->default(fn () => Setting::where('key', 'blogs_meta_title_en')->first()?->value),

                            Textarea::make('meta_description_ar')
                                ->label(__('dashboard.meta_description_ar'))
                                ->rows(3)
                                ->default(fn () => Setting::where('key', 'blogs_meta_description_ar')->first()?->value),

                            Textarea::make('meta_description_en')
                                ->label(__('dashboard.meta_description_en'))
                                ->rows(3)
                                ->default(fn () => Setting::where('key', 'blogs_meta_description_en')->first()?->value),

                            TagsInput::make('keywords_ar')
                                ->label(__('dashboard.keywords_ar'))
                                ->placeholder(__('dashboard.keywords_ar_placeholder'))
                                ->separator(' ')
                                ->default(function () {
                                    $v = Setting::where('key', 'blogs_keywords_ar')->first()?->value;
                                    return $v !== null && $v !== '' ? explode(' ', $v) : [];
                                }),

                            TagsInput::make('keywords_en')
                                ->label(__('dashboard.keywords_en'))
                                ->placeholder(__('dashboard.keywords_en_placeholder'))
                                ->separator(' ')
                                ->default(function () {
                                    $v = Setting::where('key', 'blogs_keywords_en')->first()?->value;
                                    return $v !== null && $v !== '' ? explode(' ', $v) : [];
                                }),

                            TextInput::make('canonical_url')
                                ->label(__('dashboard.canonical_url'))
                                ->url()
                                ->nullable()
                                ->default(fn () => Setting::where('key', 'blogs_canonical_url')->first()?->value),

                            FileUpload::make('og_image')
                                ->label(__('dashboard.og_image'))
                                ->directory('seo')
                                ->image()
                                ->optimize('webp')
                                ->default(fn () => Setting::where('key', 'blogs_og_image')->first()?->value),
                        ]),
                ])
                ->action(function (array $data) {
                    $tagKeys = ['keywords_ar', 'keywords_en'];

                    foreach ($data as $key => $value) {
                        if (in_array($key, $tagKeys)) {
                            Setting::updateOrCreate(
                                ['key' => 'blogs_'.$key],
                                ['value' => is_array($value) ? implode(' ', $value) : $value]
                            );
                        } else {
                            Setting::updateOrCreate(
                                ['key' => 'blogs_'.$key],
                                ['value' => $value]
                            );
                        }
                    }
                })
                // ->icon('heroicon-o-globe-alt')
                ->successNotificationTitle(__('dashboard.saved_successfully')),

            CreateAction::make(),
        ];
    }
}
