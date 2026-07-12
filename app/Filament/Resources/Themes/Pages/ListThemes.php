<?php

namespace App\Filament\Resources\Themes\Pages;

use App\Filament\Resources\Themes\ThemeResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Auth;

class ListThemes extends ListRecords
{
    protected static string $resource = ThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('seo')
                ->color('info')
                ->label(__('dashboard.section_title'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('title_ar')
                                ->label(__('dashboard.title_ar'))
                                ->default(fn () => Setting::where('key', 'themes_title_ar')->first()?->value),

                            TextInput::make('title_en')
                                ->label(__('dashboard.title_en'))
                                ->default(fn () => Setting::where('key', 'themes_title_en')->first()?->value),

                            // Textarea::make('description_ar')
                            //     ->label(__('dashboard.description_ar'))
                            //     ->rows(3)
                            //     ->default(fn () => Setting::where('key', 'themes_description_ar')->first()?->value),

                            // Textarea::make('description_en')
                            //     ->label(__('dashboard.description_en'))
                            //     ->rows(3)
                            //     ->default(fn () => Setting::where('key', 'themes_description_en')->first()?->value),
                        ]),
                ])
                ->action(function (array $data) {
                    foreach ($data as $key => $value) {
                        Setting::updateOrCreate(
                            ['key' => 'themes_'.$key],
                            ['value' => $value]
                        );
                    }
                })
                // ->icon('heroicon-o-globe-alt')
                ->successNotificationTitle(__('dashboard.saved_successfully'))
                ->visible(fn () => Auth::user()->can('themes.update')),

            CreateAction::make()
                ->visible(fn () => Auth::user()->can('themes.create')),
        ];
    }
}
