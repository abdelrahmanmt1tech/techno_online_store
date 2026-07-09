<?php

namespace App\Filament\Shared\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;
use App\WhatsApp\Actions\FindOrCreateConversationAction;
use App\WhatsApp\Actions\SendWhatsAppTemplateMessageAction;
use App\WhatsApp\Actions\SendWhatsAppTextMessageAction;
use App\WhatsApp\Actions\UpsertWhatsAppContactAction;
use App\WhatsApp\DTOs\SendTemplateMessageData;
use App\WhatsApp\DTOs\SendTextMessageData;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class SendWhatsAppMessageFilamentAction
{
    /**
     * @return array<int, Component>
     */
    public static function formSchema(bool $includePhoneField = false): array
    {
        $fields = [];

        if ($includePhoneField) {
            $fields[] = TextInput::make('phone')
                ->label(__('dashboard.whatsapp_customer_phone'))
                ->tel()
                ->required()
                ->helperText(__('dashboard.whatsapp_contact_phone_helper'));
        }

        $fields[] = Select::make('whatsapp_number_id')
            ->label(__('dashboard.whatsapp_select_reply_number'))
            ->options(fn () => WhatsAppNumber::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->pluck('display_phone_number', 'id'))
            ->default(fn () => WhatsAppNumber::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->value('id'))
            ->required()
            ->native(false);

        $fields[] = Select::make('message_type')
            ->label(__('dashboard.whatsapp_message_type'))
            ->options([
                'template' => __('dashboard.whatsapp_send_template'),
                'text' => __('dashboard.whatsapp_reply'),
            ])
            ->default('template')
            ->required()
            ->live()
            ->native(false);

        $fields[] = Select::make('template_id')
            ->label(__('dashboard.whatsapp_select_template'))
            ->options(fn () => WhatsAppTemplate::query()
                ->where('is_disabled_locally', false)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (WhatsAppTemplate $template) => [
                    $template->id => $template->name.' ('.$template->language.')',
                ]))
            ->searchable()
            ->visible(fn (callable $get): bool => $get('message_type') === 'template')
            ->required(fn (callable $get): bool => $get('message_type') === 'template')
            ->native(false);

        $fields[] = KeyValue::make('template_variables')
            ->label(__('dashboard.whatsapp_template_variables'))
            ->keyLabel(__('dashboard.whatsapp_template_variable_key'))
            ->valueLabel(__('dashboard.whatsapp_template_variable_value'))
            ->visible(fn (callable $get): bool => $get('message_type') === 'template');

        $fields[] = Textarea::make('body')
            ->label(__('dashboard.whatsapp_reply'))
            ->rows(4)
            ->visible(fn (callable $get): bool => $get('message_type') === 'text')
            ->required(fn (callable $get): bool => $get('message_type') === 'text');

        return $fields;
    }

    public static function make(
        string $name,
        callable $resolvePhone,
        ?callable $resolveName = null,
        bool $includePhoneField = false,
        ?string $label = null,
    ): Action {
        return Action::make($name)
            ->label($label ?? __('dashboard.whatsapp_send_message'))
            ->icon(Heroicon::PaperAirplane)
            ->schema(static::formSchema($includePhoneField))
            ->action(function (...$arguments) use ($resolvePhone, $resolveName, $includePhoneField): void {
                [$record, $data] = static::resolveActionArguments($arguments);

                try {
                    if ($includePhoneField) {
                        $phone = (string) ($data['phone'] ?? '');
                        $contactName = null;
                    } else {
                        $phone = (string) $resolvePhone($record);
                        $contactName = $resolveName ? $resolveName($record) : null;
                    }

                    static::dispatch($data, $phone, $contactName);

                    Notification::make()
                        ->title(__('dashboard.whatsapp_send_message_success'))
                        ->success()
                        ->send();
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->title(__('dashboard.whatsapp_send_message_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * @param  array<int, mixed>  $arguments
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    protected static function resolveActionArguments(array $arguments): array
    {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return [null, $arguments[0]];
        }

        return [$arguments[0] ?? null, is_array($arguments[1] ?? null) ? $arguments[1] : []];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function dispatch(array $data, string $phone, ?string $contactName = null): void
    {
        $number = WhatsAppNumber::query()
            ->whereKey($data['whatsapp_number_id'])
            ->where('is_active', true)
            ->firstOrFail();

        app(UpsertWhatsAppContactAction::class)->execute($phone, $contactName);

        $conversation = app(FindOrCreateConversationAction::class)->execute(
            $number,
            $phone,
            $contactName,
        );

        if (($data['message_type'] ?? 'template') === 'template') {
            $template = WhatsAppTemplate::query()->findOrFail($data['template_id']);
            $variables = is_array($data['template_variables'] ?? null) ? $data['template_variables'] : [];

            app(SendWhatsAppTemplateMessageAction::class)->execute(
                new SendTemplateMessageData($number, $conversation, $template, $variables, Auth::id()),
                Auth::user(),
            );

            return;
        }

        app(SendWhatsAppTextMessageAction::class)->execute(
            new SendTextMessageData($number, $conversation, (string) ($data['body'] ?? ''), Auth::id()),
            Auth::user(),
        );
    }
}
