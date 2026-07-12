<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.faq_details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('question.ar')
                            ->label(__('dashboard.question_ar'))
                            ->required(),

                        TextInput::make('question.en')
                            ->label(__('dashboard.question_en'))
                            ->required(),

                        Textarea::make('answer.ar')
                            ->label(__('dashboard.answer_ar'))
                            ->required()
                            ->rows(5),

                        Textarea::make('answer.en')
                            ->label(__('dashboard.answer_en'))
                            ->required()
                            ->rows(5),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.settings'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('order')
                            ->label(__('dashboard.order'))
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label(__('dashboard.active'))
                            ->default(true),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
