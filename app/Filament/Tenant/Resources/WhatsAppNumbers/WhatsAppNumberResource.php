<?php

namespace App\Filament\Tenant\Resources\WhatsAppNumbers;

use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Shared\WhatsApp\Schemas\WhatsAppNumberForm;
use App\Filament\Shared\WhatsApp\Tables\WhatsAppNumbersTable;
use App\Filament\Tenant\Resources\WhatsAppNumbers\Pages\CreateWhatsAppNumber;
use App\Filament\Tenant\Resources\WhatsAppNumbers\Pages\EditWhatsAppNumber;
use App\Filament\Tenant\Resources\WhatsAppNumbers\Pages\ListWhatsAppNumbers;
use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Actions\FindOrCreateConversationAction;
use App\WhatsApp\Actions\SendWhatsAppTextMessageAction;
use App\WhatsApp\DTOs\SendTextMessageData;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class WhatsAppNumberResource extends Resource
{
    use ChecksWhatsAppPermissions;

    protected static ?string $model = WhatsAppNumber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Phone;

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.whatsapp_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.whatsapp_numbers');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.whatsapp_numbers');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.whatsapp_number');
    }

    public static function canViewAny(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_numbers');
    }

    public static function canCreate(): bool
    {
        return static::canWhatsAppPermission('whatsapp.manage_numbers');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWhatsAppPermission('whatsapp.manage_numbers');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canWhatsAppPermission('whatsapp.manage_numbers');
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsAppNumberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsAppNumbersTable::configure($table)
            ->recordActions([
                Action::make('setDefault')
                    ->label(__('dashboard.whatsapp_set_default'))
                    ->icon(Heroicon::Star)
                    ->visible(fn () => static::canWhatsAppPermission('whatsapp.manage_numbers'))
                    ->action(function (WhatsAppNumber $record): void {
                        WhatsAppNumber::query()->update(['is_default' => false]);
                        $record->update(['is_default' => true]);
                    }),
                Action::make('sendTest')
                    ->label(__('dashboard.whatsapp_send_test_message'))
                    ->icon(Heroicon::PaperAirplane)
                    ->visible(fn () => static::canWhatsAppPermission('whatsapp.send_messages'))
                    ->schema([
                        TextInput::make('recipient')
                            ->label(__('dashboard.whatsapp_test_recipient'))
                            ->required(),
                        Textarea::make('body')
                            ->label(__('dashboard.whatsapp_test_message_body'))
                            ->required(),
                    ])
                    ->action(function (WhatsAppNumber $record, array $data): void {
                        $conversation = app(FindOrCreateConversationAction::class)->execute(
                            $record,
                            $data['recipient'],
                        );

                        try {
                            app(SendWhatsAppTextMessageAction::class)->execute(
                                new SendTextMessageData($record, $conversation, $data['body'], Auth::id()),
                                Auth::user(),
                            );
                            Notification::make()->title(__('dashboard.whatsapp_send_test_message'))->success()->send();
                        } catch (\Throwable $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppNumbers::route('/'),
            'create' => CreateWhatsAppNumber::route('/create'),
            'edit' => EditWhatsAppNumber::route('/{record}/edit'),
        ];
    }
}
