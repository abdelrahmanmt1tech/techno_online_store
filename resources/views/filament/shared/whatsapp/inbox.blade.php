<div class="wa-inbox-layout">
    <div class="wa-inbox-sidebar">
        <div class="wa-inbox-sidebar__title">{{ __('dashboard.whatsapp_inbox') }}</div>
        <div class="wa-conversation-list wa-panel">
            @forelse ($this->conversations as $conversation)
                <button
                    type="button"
                    wire:click="selectConversation({{ $conversation->id }})"
                    class="wa-conversation-item {{ $selectedConversationId === $conversation->id ? 'wa-conversation-item--active' : '' }}"
                >
                    <div class="wa-conversation-item__name">{{ $conversation->customer_name ?: $conversation->customer_phone }}</div>
                    <div class="wa-conversation-item__preview">{{ $conversation->last_message_preview }}</div>
                    <div class="wa-conversation-item__time">{{ $conversation->last_message_at?->diffForHumans() }}</div>
                </button>
            @empty
                <div class="wa-empty-state" style="border: 0; border-radius: 0;">{{ __('dashboard.no_categories') }}</div>
            @endforelse
        </div>
    </div>

    <div class="wa-inbox-main">
        @if ($conversation = $this->selectedConversation)
            <div class="wa-conversation-header wa-panel">
                <div>
                    <div class="wa-conversation-header__name">{{ $conversation->customer_name ?: $conversation->customer_phone }}</div>
                    <div class="wa-conversation-header__phone">{{ $conversation->whatsappNumber?->display_phone_number }}</div>
                </div>
                <div class="wa-window-status">
                    @if ($this->canSendFreeform)
                        <span class="wa-badge wa-badge--success">
                            {{ __('dashboard.whatsapp_window_open') }}
                        </span>
                        <div class="wa-window-status__meta">
                            {{ __('dashboard.whatsapp_window_expires_at', ['time' => $conversation->customer_service_window_expires_at?->format('Y-m-d H:i')]) }}
                        </div>
                    @else
                        <span class="wa-badge wa-badge--danger">
                            {{ __('dashboard.whatsapp_window_closed') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="wa-messages wa-panel">
                @foreach ($this->messages as $message)
                    <div class="wa-message {{ $message->direction->value === 'outbound' ? 'wa-message--outbound' : 'wa-message--inbound' }}">
                        <div class="wa-message__bubble">
                            <div>{{ $message->body ?: '['.$message->type->value.']' }}</div>
                            <div class="wa-message__meta">{{ $message->status->value }} · {{ $message->created_at?->format('H:i') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($this->canSendMessages || $this->canSendTemplates)
                <div class="wa-reply-panel wa-panel">
                    @if ($this->canSwitchReplyNumber && $this->availableNumbers->count() > 1)
                        <select wire:model.live="replyNumberId" class="wa-select">
                            @foreach ($this->availableNumbers as $number)
                                <option value="{{ $number->id }}">{{ $number->display_phone_number }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if ($this->canSendFreeform && $this->canSendMessages)
                        <textarea wire:model="replyBody" rows="3" class="wa-textarea" placeholder="{{ __('dashboard.whatsapp_reply') }}"></textarea>
                        <div class="wa-actions">
                            <x-filament::button wire:click="sendReplyAction">{{ __('dashboard.whatsapp_reply') }}</x-filament::button>
                        </div>
                    @elseif (! $this->canSendFreeform)
                        <div class="wa-alert">{{ __('dashboard.whatsapp_window_closed_message') }}</div>
                    @endif

                    @if ($this->canSendTemplates)
                        <div class="wa-reply-panel__divider wa-template-fields">
                            <select wire:model.live="selectedTemplateId" class="wa-select">
                                <option value="">{{ __('dashboard.whatsapp_select_template') }}</option>
                                @foreach ($this->availableTemplates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->language }})</option>
                                @endforeach
                            </select>

                            @foreach ($templateVariables as $key => $value)
                                <input type="text" wire:model="templateVariables.{{ $key }}" class="wa-input" placeholder="{{ $key }}">
                            @endforeach

                            <div class="wa-actions">
                                <x-filament::button color="gray" wire:click="sendTemplateReplyAction">{{ __('dashboard.whatsapp_send_template') }}</x-filament::button>
                            </div>
                        </div>
                    @endif

                    <div class="wa-actions">
                        <x-filament::button color="gray" wire:click="toggleConversationStatus">
                            {{ $conversation->status->value === 'closed' ? __('dashboard.whatsapp_open_conversation') : __('dashboard.whatsapp_close_conversation') }}
                        </x-filament::button>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
