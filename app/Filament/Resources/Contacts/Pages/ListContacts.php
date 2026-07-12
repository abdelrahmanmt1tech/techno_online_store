<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Auth;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make()
            //     ->visible(fn () => Auth::user()->can('contacts.create')),

            // Action::make('titles')
            //     ->color('info')
            //     ->label(__('dashboard.title'))
            //     ->schema([
            //         FileUpload::make('contact_us_image')
            //             ->label(__('dashboard.image'))
            //             ->image()
            //             ->directory('contact-us')
            //             ->optimize('webp')
            //             ->columnSpanFull()
            //             ->default(fn() => Setting::where('key', 'contact_us_image')->first()?->value),

            //         Grid::make(2)
            //             ->schema([
            //                 Textarea::make("contact_us_description_ar")
            //                     ->label(__('dashboard.description_ar'))
            //                     ->rows(3)
            //                     ->default(fn() => Setting::where('key', 'contact_us_description_ar')->first()?->value),

            //                 Textarea::make("contact_us_description_en")
            //                     ->label(__('dashboard.description_en'))
            //                     ->rows(3)
            //                     ->default(fn() => Setting::where('key', 'contact_us_description_en')->first()?->value),
            //             ])
            //     ])
            //     ->action(function (array $data) {
            //         Setting::updateOrCreate(
            //             ['key' => 'contact_us_image'],
            //             ['value' => $data['contact_us_image'] ?? null]
            //         );
            //         Setting::updateOrCreate(
            //             ['key' => 'contact_us_description_ar'],
            //             ['value' => $data['contact_us_description_ar']]
            //         );
            //         Setting::updateOrCreate(
            //             ['key' => 'contact_us_description_en'],
            //             ['value' => $data['contact_us_description_en']]
            //         );
            //     })
            //     ->icon('heroicon-o-document-text')
            //     ->successNotificationTitle(__('dashboard.saved_successfully'))
            //     ->visible(fn() => Auth::user()->can('contacts.update')),

        ];
    }
}
