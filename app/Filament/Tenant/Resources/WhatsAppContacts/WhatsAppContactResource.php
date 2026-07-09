<?php

namespace App\Filament\Tenant\Resources\WhatsAppContacts;

use App\Filament\Shared\WhatsApp\Actions\SendWhatsAppMessageFilamentAction;
use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Shared\WhatsApp\Schemas\WhatsAppContactForm;
use App\Filament\Shared\WhatsApp\Tables\WhatsAppContactsTable;
use App\Filament\Tenant\Pages\WhatsAppInboxPage;
use App\Filament\Tenant\Resources\WhatsAppContacts\Pages\CreateWhatsAppContact;
use App\Filament\Tenant\Resources\WhatsAppContacts\Pages\EditWhatsAppContact;
use App\Filament\Tenant\Resources\WhatsAppContacts\Pages\ListWhatsAppContacts;
use App\Models\Tenant\WhatsAppContact;
use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Actions\FindOrCreateConversationAction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WhatsAppContactResource extends Resource
{
    use ChecksWhatsAppPermissions;

    protected static ?string $model = WhatsAppContact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?int $navigationSort = 43;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.whatsapp_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.whatsapp_contacts');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.whatsapp_contacts');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.whatsapp_contact');
    }

    public static function canViewAny(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_inbox');
    }

    public static function canCreate(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_inbox');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_inbox');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_inbox');
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsAppContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsAppContactsTable::configure($table)
            ->recordActions([
                SendWhatsAppMessageFilamentAction::make(
                    name: 'sendMessage',
                    resolvePhone: fn (WhatsAppContact $record) => $record->phone,
                    resolveName: fn (WhatsAppContact $record) => $record->profile_name,
                ),
                Action::make('openInbox')
                    ->label(__('dashboard.whatsapp_open_inbox'))
                    ->icon(Heroicon::ChatBubbleLeftRight)
                    ->url(function (WhatsAppContact $record): string {
                        $number = WhatsAppNumber::query()
                            ->where('is_active', true)
                            ->orderByDesc('is_default')
                            ->first();

                        $conversationId = null;

                        if ($number !== null) {
                            $conversation = app(FindOrCreateConversationAction::class)->execute(
                                $number,
                                $record->phone,
                                $record->profile_name,
                            );
                            $conversationId = $conversation->id;
                        }

                        return WhatsAppInboxPage::getUrl([
                            'conversation' => $conversationId,
                        ]);
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppContacts::route('/'),
            'create' => CreateWhatsAppContact::route('/create'),
            'edit' => EditWhatsAppContact::route('/{record}/edit'),
        ];
    }
}
