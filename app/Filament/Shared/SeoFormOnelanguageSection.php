<?php

namespace App\Filament\Shared;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class SeoFormOnelanguageSection
{
    public static function make(): Section
    {
        return Section::make(__('dashboard.seo_settings'))
            ->columns(2)
            ->relationship('seo')
            ->schema([
                TextInput::make('meta_title')->label(__('dashboard.meta_title')),
                Textarea::make('meta_description')->label(__('dashboard.meta_description'))->rows(3),

                TagsInput::make('keywords')->label(__('dashboard.keywords'))->placeholder(__('dashboard.keywords_placeholder'))->separator(' '),

                TextInput::make('canonical_url')->label(__('dashboard.canonical_url'))->url()->nullable(),
                FileUpload::make('og_image')->label(__('dashboard.og_image'))->directory('seo')->image()->optimize('webp'),
            ])
            ->collapsible()
            ->collapsed();
    }
}
