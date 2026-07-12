<?php

namespace App\Filament\Shared;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class SeoFormSection
{
    public static function make(): Section
    {
        return Section::make(__('dashboard.seo_settings'))
            ->columns(2)
            ->relationship('seo')
            ->schema([
                TextInput::make('meta_title.ar')->label(__('dashboard.meta_title_ar')),
                TextInput::make('meta_title.en')->label(__('dashboard.meta_title_en')),

                Textarea::make('meta_description.ar')->label(__('dashboard.meta_description_ar'))->rows(3),
                Textarea::make('meta_description.en')->label(__('dashboard.meta_description_en'))->rows(3),

                TagsInput::make('keywords.ar')->label(__('dashboard.keywords_ar'))->placeholder(__('dashboard.keywords_ar_placeholder'))->separator(' '),
                TagsInput::make('keywords.en')->label(__('dashboard.keywords_en'))->placeholder(__('dashboard.keywords_en_placeholder'))->separator(' '),

                TextInput::make('canonical_url')->label(__('dashboard.canonical_url'))->url()->nullable(),
                FileUpload::make('og_image')->label(__('dashboard.og_image'))->directory('seo')->image()->optimize('webp'),
            ])
            ->collapsible()
            ->collapsed();
    }
}
