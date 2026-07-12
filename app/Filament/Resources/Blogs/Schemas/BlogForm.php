<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Filament\Shared\SeoFormSection;
use App\Models\BlogCategory;
use App\Models\Tag;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.blog_details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('title.ar')
                            ->label(__('dashboard.title_ar'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('title.en')
                            ->label(__('dashboard.title_en'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label(__('dashboard.slug'))
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        FileUpload::make('image')
                            ->label(__('dashboard.image'))
                            ->image()
                            ->required()
                            ->directory('blogs'),

                        Textarea::make('description.ar')
                            ->label(__('dashboard.description_ar'))
                            ->required()
                            ->rows(3),

                        Textarea::make('description.en')
                            ->label(__('dashboard.description_en'))
                            ->required()
                            ->rows(3),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.blog_content'))
                    ->columns(2)
                    ->schema([
                        RichEditor::make('content.ar')
                            ->label(__('dashboard.content_ar'))
                            ->required()
                            ->columnSpan(1),

                        RichEditor::make('content.en')
                            ->label(__('dashboard.content_en'))
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.blog_relations'))
                    ->columns(2)
                    ->schema([
                        Select::make('categories')
                            ->required()
                            ->label(__('dashboard.blog_categories'))
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->suffixAction(
                                Action::make('add_category')
                                    ->icon('heroicon-o-plus-circle')
                                    ->schema([
                                        Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('name.ar')
                                                    ->label(__('dashboard.name_ar'))
                                                    ->required()
                                                    ->maxLength(255),

                                                TextInput::make('name.en')
                                                    ->label(__('dashboard.name_en'))
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),
                                    ])
                                    ->action(function (array $data, Get $get, Set $set) {
                                        $category = BlogCategory::create([
                                            'name' => $data['name'],
                                            'slug' => Str::slug(
                                                $data['name']['en'] ?? $data['name']['ar']
                                            ),
                                        ]);

                                        $currentCategories = $get('categories') ?? [];

                                        $set(
                                            'categories',
                                            array_unique([
                                                ...$currentCategories,
                                                $category->id,
                                            ])
                                        );
                                    })
                            ),

                        Select::make('tags')
                            ->required()
                            ->label(__('dashboard.blog_tags'))
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->suffixAction(
                                Action::make('add_tag')
                                    ->icon('heroicon-o-plus-circle')
                                    ->schema([
                                        Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('name.ar')
                                                    ->label(__('dashboard.name_ar'))
                                                    ->required()
                                                    ->maxLength(255),

                                                TextInput::make('name.en')
                                                    ->label(__('dashboard.name_en'))
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),
                                    ])
                                    ->action(function (array $data, Get $get, Set $set) {
                                        $tag = Tag::create([
                                            'name' => $data['name'],
                                            'slug' => Str::slug(
                                                $data['name']['en'] ?? $data['name']['ar']
                                            ),
                                        ]);

                                        $currentTags = $get('tags') ?? [];

                                        $set(
                                            'tags',
                                            array_unique([
                                                ...$currentTags,
                                                $tag->id,
                                            ])
                                        );
                                    })
                            ),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.settings'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('order')
                            ->label(__('dashboard.order'))
                            ->numeric()
                            ->default(0),

                        DatePicker::make('published_at')
                            ->label(__('dashboard.published_at')),

                        Toggle::make('is_featured')
                            ->label(__('dashboard.featured'))
                            ->default(false),

                        Toggle::make('is_active')
                            ->label(__('dashboard.active'))
                            ->default(true),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.faqs'))
                    ->schema([
                        Repeater::make('faqs')
                            ->label(__('dashboard.faqs'))
                            ->relationship()
                            ->schema([
                                TextInput::make('question.ar')
                                    ->label(__('dashboard.question_ar'))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('question.en')
                                    ->label(__('dashboard.question_en'))
                                    ->required()
                                    ->maxLength(255),

                                Textarea::make('answer.ar')
                                    ->label(__('dashboard.answer_ar'))
                                    ->required()
                                    ->rows(3),

                                Textarea::make('answer.en')
                                    ->label(__('dashboard.answer_en'))
                                    ->required()
                                    ->rows(3),

                                TextInput::make('order')
                                    ->label(__('dashboard.order'))
                                    ->numeric()
                                    ->default(0),

                                Toggle::make('is_active')
                                    ->label(__('dashboard.active'))
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel(__('dashboard.add_faq'))
                            ->itemLabel(fn (array $state): ?string => $state['question']['en'] ?? $state['question']['ar'] ?? null),
                    ])
                    ->columnSpanFull(),

                SeoFormSection::make()->columnSpanFull(),

            ]);
    }
}
