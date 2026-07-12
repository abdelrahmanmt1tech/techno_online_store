<div class="wa-inbox-layout">
    <div class="wa-inbox-sidebar">
        <div class="wa-inbox-sidebar__title">{{ __('dashboard.messenger_inbox') }}</div>
        <div class="wa-conversation-list wa-panel">
            @forelse ($this->conversations as $conversation)
                <button
                    type="button"
                    wire:click="selectConversation({{ $conversation->id }})"
                    class="wa-conversation-item {{ $selectedConversationId === $conversation->id ? 'wa-conversation-item--active' : '' }}"
                >
                    <div class="wa-conversation-item__name">
                        {{ $conversation->customer_name ?: $conversation->contact?->profile_name ?: $conversation->sender_psid }}
                    </div>
                    <div class="wa-conversation-item__preview">{{ $conversation->last_message_preview }}</div>
                    <div class="wa-conversation-item__time">
                        {{ $conversation->messengerPage?->page_name ?: $conversation->messengerPage?->page_id }}
                        · {{ $conversation->last_message_at?->diffForHumans() }}
                    </div>
                </button>
            @empty
                <div class="wa-empty-state" style="border: 0; border-radius: 0;">{{ __('dashboard.messenger_inbox_empty') }}</div>
            @endforelse
        </div>
    </div>

    <div class="wa-inbox-main">
        @if ($conversation = $this->selectedConversation)
            <div class="wa-conversation-header wa-panel">
                <div>
                    <div class="wa-conversation-header__name">
                        {{ $conversation->customer_name ?: $conversation->contact?->profile_name ?: $conversation->sender_psid }}
                    </div>
                    <div class="wa-conversation-header__phone">
                        {{ __('dashboard.messenger_page') }}:
                        {{ $conversation->messengerPage?->page_name ?: $conversation->messengerPage?->page_id ?: '—' }}
                        · PSID: {{ $conversation->sender_psid }}
                    </div>
                </div>
                <div class="wa-window-status">
                    @if ($this->canSendFreeform)
                        <span class="wa-badge wa-badge--success">
                            {{ __('dashboard.messenger_window_open') }}
                        </span>
                        <div class="wa-window-status__meta">
                            {{ __('dashboard.messenger_window_expires_at', ['time' => $conversation->customer_service_window_expires_at?->format('Y-m-d H:i')]) }}
                        </div>
                    @else
                        <span class="wa-badge wa-badge--danger">
                            {{ __('dashboard.messenger_window_closed') }}
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

            @if ($this->canSendMessages)
                <div class="wa-reply-panel wa-panel">
                    @if ($this->canReply)
                        <textarea wire:model="replyBody" rows="3" class="wa-textarea" placeholder="{{ __('dashboard.messenger_reply') }}"></textarea>
                        <div class="wa-actions">
                            <x-filament::button wire:click="sendReplyAction">{{ __('dashboard.messenger_reply') }}</x-filament::button>
                        </div>
                    @else
                        <div class="wa-alert">{{ __('dashboard.messenger_window_closed_message') }}</div>
                    @endif

                    <div class="wa-actions">
                        <x-filament::button color="gray" wire:click="toggleConversationStatus">
                            {{ $conversation->status->value === 'closed' ? __('dashboard.messenger_open_conversation') : __('dashboard.messenger_close_conversation') }}
                        </x-filament::button>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
