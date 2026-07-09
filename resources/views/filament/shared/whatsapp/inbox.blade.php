<x-filament-panels::page>
    <div class="grid gap-4 lg:grid-cols-12">
        <div class="lg:col-span-4 space-y-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('dashboard.whatsapp_inbox') }}</div>
            <div class="divide-y rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                @forelse ($this->conversations as $conversation)
                    <button
                        type="button"
                        wire:click="selectConversation({{ $conversation->id }})"
                        class="w-full text-start px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 {{ $selectedConversationId === $conversation->id ? 'bg-primary-50 dark:bg-primary-500/10' : '' }}"
                    >
                        <div class="font-medium">{{ $conversation->customer_name ?: $conversation->customer_phone }}</div>
                        <div class="text-xs text-gray-500">{{ $conversation->last_message_preview }}</div>
                        <div class="text-xs text-gray-400">{{ $conversation->last_message_at?->diffForHumans() }}</div>
                    </button>
                @empty
                    <div class="px-4 py-6 text-sm text-gray-500">{{ __('dashboard.no_categories') }}</div>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-8 space-y-4">
            @if ($conversation = $this->selectedConversation)
                <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div>
                        <div class="font-semibold">{{ $conversation->customer_name ?: $conversation->customer_phone }}</div>
                        <div class="text-sm text-gray-500">{{ $conversation->whatsappNumber?->display_phone_number }}</div>
                    </div>
                    <div class="text-end">
                        @if ($this->canSendFreeform)
                            <span class="inline-flex items-center rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-700">
                                {{ __('dashboard.whatsapp_window_open') }}
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ __('dashboard.whatsapp_window_expires_at', ['time' => $conversation->customer_service_window_expires_at?->format('Y-m-d H:i')]) }}
                            </div>
                        @else
                            <span class="inline-flex items-center rounded-full bg-danger-50 px-3 py-1 text-xs font-medium text-danger-700">
                                {{ __('dashboard.whatsapp_window_closed') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 min-h-80 max-h-[32rem] overflow-y-auto space-y-3">
                    @foreach ($this->messages as $message)
                        <div class="{{ $message->direction->value === 'outbound' ? 'text-end' : 'text-start' }}">
                            <div class="inline-block max-w-[80%] rounded-2xl px-4 py-2 text-sm {{ $message->direction->value === 'outbound' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800' }}">
                                <div>{{ $message->body ?: '['.$message->type->value.']' }}</div>
                                <div class="text-[10px] opacity-70 mt-1">{{ $message->status->value }} · {{ $message->created_at?->format('H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($this->canSendMessages || $this->canSendTemplates)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                        @if ($this->canSwitchReplyNumber && $this->availableNumbers->count() > 1)
                            <select wire:model.live="replyNumberId" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900">
                                @foreach ($this->availableNumbers as $number)
                                    <option value="{{ $number->id }}">{{ $number->display_phone_number }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if ($this->canSendFreeform && $this->canSendMessages)
                            <textarea wire:model="replyBody" rows="3" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900" placeholder="{{ __('dashboard.whatsapp_reply') }}"></textarea>
                            <x-filament::button wire:click="sendReplyAction">{{ __('dashboard.whatsapp_reply') }}</x-filament::button>
                        @elseif (! $this->canSendFreeform)
                            <div class="text-sm text-danger-600">{{ __('dashboard.whatsapp_window_closed_message') }}</div>
                        @endif

                        @if ($this->canSendTemplates)
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 space-y-2">
                                <select wire:model.live="selectedTemplateId" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900">
                                    <option value="">{{ __('dashboard.whatsapp_select_template') }}</option>
                                    @foreach ($this->availableTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->language }})</option>
                                    @endforeach
                                </select>

                                @foreach ($templateVariables as $key => $value)
                                    <input type="text" wire:model="templateVariables.{{ $key }}" class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900" placeholder="{{ $key }}">
                                @endforeach

                                <x-filament::button color="gray" wire:click="sendTemplateReplyAction">{{ __('dashboard.whatsapp_send_template') }}</x-filament::button>
                            </div>
                        @endif

                        <x-filament::button color="gray" wire:click="toggleConversationStatus">
                            {{ $conversation->status->value === 'closed' ? __('dashboard.whatsapp_open_conversation') : __('dashboard.whatsapp_close_conversation') }}
                        </x-filament::button>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>
