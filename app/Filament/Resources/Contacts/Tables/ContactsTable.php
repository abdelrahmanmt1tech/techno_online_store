<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Models\Contact;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.sender_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label(__('dashboard.sender_phone'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return $state ?: '-';
                    })
                    ->url(function ($record) {
                        if ($record->phone) {
                            return 'https://wa.me/'.preg_replace('/[^0-9]/', '', $record->phone);
                        }

                        return null;
                    })
                    ->openUrlInNewTab()
                    ->tooltip(fn ($record) => $record->phone ? __('dashboard.open_whatsapp') : null)
                    ->icon(fn ($record) => $record->phone ? 'heroicon-o-chat-bubble-oval-left' : null)
                    ->iconPosition('after')
                    ->color('success')
                    ->openUrlInNewTab()
                    ->tooltip(fn ($record) => $record->phone ? __('dashboard.open_whatsapp') : null)
                    ->icon(fn ($record) => $record->phone ? 'heroicon-o-chat-bubble-oval-left' : null)
                    ->iconPosition('after')
                    ->color('success'),

                TextColumn::make('email')
                    ->label(__('dashboard.sender_email'))
                    ->searchable()
                    ->sortable(),

                IconColumn::make('read_at')
                    ->label(__('dashboard.read_status'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->getStateUsing(fn ($record) => $record->read_at !== null),

                SelectColumn::make('status')
                    ->label(__('dashboard.status'))
                    ->options([
                        'pending' => __('dashboard.pending'),
                        'on_progress' => __('dashboard.on_progress'),
                        'completed' => __('dashboard.completed_status'),
                    ])
                    ->selectablePlaceholder(false)
                    ->beforeStateUpdated(function () {
                        abort_unless(auth()->user()->can('contacts.update'), 403);
                    })
                    ->disabled(fn (): bool => ! Auth::user()->can('contacts.update')),

                TextColumn::make('created_at')
                    ->label(__('dashboard.message_date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_read')
                    ->label(__('dashboard.read_status_filter'))
                    ->options([
                        '1' => __('dashboard.read'),
                        '0' => __('dashboard.unread_status'),
                    ])
                    ->native(false)
                    ->query(function ($query, $data) {
                        if ($data['value'] === '1') {
                            return $query->whereNotNull('read_at');
                        } elseif ($data['value'] === '0') {
                            return $query->whereNull('read_at');
                        }

                        return $query;
                    }),

                SelectFilter::make('status')
                    ->label(__('dashboard.status_filter'))
                    ->options([
                        'pending' => __('dashboard.pending'),
                        'on_progress' => __('dashboard.on_progress'),
                        'completed' => __('dashboard.completed_status'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()
                    ->label(__('dashboard.show_message'))
                    ->visible(fn (Contact $record) => Auth::user()->can('contacts.view'))
                    ->modalContent(function (Contact $record) {
                        if (! $record->read_at) {
                            $record->update(['read_at' => now()]);
                        }
                    }),
                DeleteAction::make()
                    ->visible(fn (Contact $record) => Auth::user()->can('contacts.delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('dashboard.delete_selected'))
                        ->visible(fn () => Auth::user()->can('contacts.delete')),
                ]),
            ]);
    }
}
