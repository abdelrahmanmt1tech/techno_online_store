<?php

namespace App\Filament\Shared\Messenger\Concerns;

use App\Messenger\Actions\SendMessengerTextMessageAction;
use App\Messenger\Enums\MessengerConversationStatus;
use App\Messenger\Services\MessengerSendingPolicyService;
use App\Models\Tenant\MessengerConversation;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

trait InteractsWithMessengerInbox
{
    public ?int $selectedConversationId = null;

    public string $replyBody = '';

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
        $this->replyBody = '';
        unset($this->messages, $this->selectedConversation, $this->canSendFreeform, $this->canReply);
    }

    #[Computed]
    public function conversations(): Collection
    {
        if (! $this->tenantContextReady()) {
            return collect();
        }

        return MessengerConversation::query()
            ->with(['messengerPage', 'contact', 'assignedUser'])
            ->latest('last_message_at')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function selectedConversation(): ?MessengerConversation
    {
        return $this->getSelectedConversation();
    }

    #[Computed]
    public function messages(): Collection
    {
        $conversation = $this->getSelectedConversation();

        if ($conversation === null) {
            return collect();
        }

        return $conversation->messages()
            ->with('messengerPage')
            ->orderBy('created_at')
            ->get();
    }

    public function sendReplyAction(): void
    {
        $this->sendReply();
    }

    public function sendReply(): void
    {
        if (! $this->tenantContextReady()) {
            Notification::make()->title(__('dashboard.messenger_select_tenant_required'))->danger()->send();

            return;
        }

        $conversation = $this->getSelectedConversation();

        if ($conversation === null) {
            return;
        }

        $page = $conversation->messengerPage;

        if ($page === null) {
            Notification::make()->title(__('dashboard.messenger_page_inactive'))->danger()->send();

            return;
        }

        $policy = app(MessengerSendingPolicyService::class)->canSendText($page, $conversation);

        if (! $policy->allowed) {
            Notification::make()->title($policy->reason ?? __('dashboard.messenger_window_closed_message'))->danger()->send();

            return;
        }

        $body = trim($this->replyBody);

        if ($body === '') {
            return;
        }

        try {
            app(SendMessengerTextMessageAction::class)->execute(
                $conversation,
                $body,
                Auth::user(),
            );

            $this->replyBody = '';
            unset($this->messages, $this->conversations, $this->selectedConversation, $this->canSendFreeform, $this->canReply);

            Notification::make()->title(__('dashboard.messenger_reply_sent'))->success()->send();
        } catch (\Throwable $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function toggleConversationStatus(): void
    {
        if (! $this->tenantContextReady()) {
            return;
        }

        $conversation = $this->getSelectedConversation();

        if ($conversation === null) {
            return;
        }

        $conversation->update([
            'status' => $conversation->status === MessengerConversationStatus::Closed
                ? MessengerConversationStatus::Open
                : MessengerConversationStatus::Closed,
        ]);

        unset($this->conversations, $this->selectedConversation);
    }

    protected function getSelectedConversation(): ?MessengerConversation
    {
        if ($this->selectedConversationId === null || ! $this->tenantContextReady()) {
            return null;
        }

        return MessengerConversation::query()
            ->with(['messengerPage', 'contact', 'assignedUser'])
            ->find($this->selectedConversationId);
    }

    protected function tenantContextReady(): bool
    {
        return tenancy()->initialized;
    }

    #[Computed]
    public function canSendFreeform(): bool
    {
        $conversation = $this->getSelectedConversation();

        return $conversation?->canSendFreeformReply() ?? false;
    }

    #[Computed]
    public function canReply(): bool
    {
        $conversation = $this->getSelectedConversation();
        $page = $conversation?->messengerPage;

        if ($conversation === null || $page === null) {
            return false;
        }

        return app(MessengerSendingPolicyService::class)->canSendText($page, $conversation)->allowed;
    }

    #[Computed]
    public function canSendMessages(): bool
    {
        if (config('app.bypass_permissions')) {
            return true;
        }

        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        if (filament()->getCurrentPanel()?->getId() === 'admin') {
            return $user->can('messenger.platform.troubleshoot');
        }

        return $user->can('messenger.send_messages');
    }
}
