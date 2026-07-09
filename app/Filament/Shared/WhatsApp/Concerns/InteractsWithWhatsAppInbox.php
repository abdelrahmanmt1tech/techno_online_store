<?php

namespace App\Filament\Shared\WhatsApp\Concerns;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;
use App\WhatsApp\Actions\FindOrCreateConversationAction;
use App\WhatsApp\Actions\SendWhatsAppTemplateMessageAction;
use App\WhatsApp\Actions\SendWhatsAppTextMessageAction;
use App\WhatsApp\DTOs\SendTemplateMessageData;
use App\WhatsApp\DTOs\SendTextMessageData;
use App\WhatsApp\Enums\WhatsAppConversationStatus;
use App\WhatsApp\Services\WhatsAppTemplateVariableValidator;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

trait InteractsWithWhatsAppInbox
{
    public ?int $selectedConversationId = null;

    public string $replyBody = '';

    public ?int $replyNumberId = null;

    public ?int $selectedTemplateId = null;

    /** @var array<string, string> */
    public array $templateVariables = [];

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
        $conversation = $this->getSelectedConversation();

        if ($conversation) {
            $this->replyNumberId = $conversation->whatsapp_number_id;
        }
    }

    #[Computed]
    public function conversations(): Collection
    {
        return WhatsAppConversation::query()
            ->with(['whatsappNumber', 'assignedUser'])
            ->latest('last_message_at')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function selectedConversation(): ?WhatsAppConversation
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
            ->with('whatsappNumber')
            ->orderBy('created_at')
            ->get();
    }

    #[Computed]
    public function availableNumbers(): Collection
    {
        return WhatsAppNumber::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->get();
    }

    #[Computed]
    public function availableTemplates(): Collection
    {
        return WhatsAppTemplate::query()
            ->where('is_disabled_locally', false)
            ->orderBy('name')
            ->get();
    }

    public function sendReplyAction(): void
    {
        $guard = filament()->getCurrentPanel()?->getId() === 'admin' ? 'admin' : 'tenant';
        $this->sendReply($guard);
    }

    public function sendTemplateReplyAction(): void
    {
        $guard = filament()->getCurrentPanel()?->getId() === 'admin' ? 'admin' : 'tenant';
        $this->sendTemplateReply($guard);
    }

    #[Computed]
    public function canSwitchReplyNumber(): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        if (filament()->getCurrentPanel()?->getId() === 'admin') {
            return $user->can('whatsapp.platform.troubleshoot');
        }

        return $user->can('whatsapp.switch_reply_number');
    }

    public function sendReply(string $guard = 'tenant'): void
    {
        $conversation = $this->getSelectedConversation();

        if ($conversation === null) {
            return;
        }

        $number = $this->resolveReplyNumber($conversation);

        if ($number === null) {
            Notification::make()->title(__('dashboard.whatsapp_number_inactive'))->danger()->send();

            return;
        }

        $targetConversation = app(FindOrCreateConversationAction::class)->execute(
            $number,
            $conversation->customer_phone,
            $conversation->customer_name,
        );

        try {
            app(SendWhatsAppTextMessageAction::class)->execute(
                new SendTextMessageData($number, $targetConversation, trim($this->replyBody), Auth::id()),
                Auth::user(),
                $guard,
            );

            $this->replyBody = '';
            $this->selectedConversationId = $targetConversation->id;
            unset($this->messages, $this->conversations, $this->selectedConversation);

            Notification::make()->title(__('dashboard.whatsapp_reply'))->success()->send();
        } catch (\Throwable $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function sendTemplateReply(string $guard = 'tenant'): void
    {
        $conversation = $this->getSelectedConversation();
        $template = WhatsAppTemplate::query()->find($this->selectedTemplateId);

        if ($conversation === null || $template === null) {
            return;
        }

        $number = $this->resolveReplyNumber($conversation);

        if ($number === null) {
            Notification::make()->title(__('dashboard.whatsapp_number_inactive'))->danger()->send();

            return;
        }

        $targetConversation = app(FindOrCreateConversationAction::class)->execute(
            $number,
            $conversation->customer_phone,
            $conversation->customer_name,
        );

        try {
            app(SendWhatsAppTemplateMessageAction::class)->execute(
                new SendTemplateMessageData($number, $targetConversation, $template, $this->templateVariables, Auth::id()),
                Auth::user(),
                $guard,
            );

            $this->templateVariables = [];
            $this->selectedTemplateId = null;
            $this->selectedConversationId = $targetConversation->id;
            unset($this->messages, $this->conversations, $this->selectedConversation);

            Notification::make()->title(__('dashboard.whatsapp_send_template'))->success()->send();
        } catch (\Throwable $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function toggleConversationStatus(): void
    {
        $conversation = $this->getSelectedConversation();

        if ($conversation === null) {
            return;
        }

        $conversation->update([
            'status' => $conversation->status === WhatsAppConversationStatus::Closed
                ? WhatsAppConversationStatus::Open
                : WhatsAppConversationStatus::Closed,
        ]);

        unset($this->conversations, $this->selectedConversation);
    }

    public function updatedSelectedTemplateId(): void
    {
        $template = WhatsAppTemplate::query()->find($this->selectedTemplateId);
        $this->templateVariables = [];

        if ($template === null) {
            return;
        }

        $placeholders = app(WhatsAppTemplateVariableValidator::class)->requiredPlaceholders($template);

        foreach ($placeholders as $index => $placeholder) {
            $this->templateVariables[(string) $index] = '';
        }
    }

    protected function getSelectedConversation(): ?WhatsAppConversation
    {
        if ($this->selectedConversationId === null) {
            return null;
        }

        return WhatsAppConversation::query()
            ->with(['whatsappNumber', 'assignedUser'])
            ->find($this->selectedConversationId);
    }

    protected function resolveReplyNumber(WhatsAppConversation $conversation): ?WhatsAppNumber
    {
        if ($this->replyNumberId && $this->canSwitchReplyNumber) {
            return WhatsAppNumber::query()
                ->whereKey($this->replyNumberId)
                ->where('is_active', true)
                ->first();
        }

        return $conversation->whatsappNumber;
    }

    #[Computed]
    public function targetConversationForReply(): ?WhatsAppConversation
    {
        $conversation = $this->getSelectedConversation();
        $number = $conversation ? $this->resolveReplyNumber($conversation) : null;

        if ($conversation === null || $number === null) {
            return null;
        }

        if ($number->id === $conversation->whatsapp_number_id) {
            return $conversation;
        }

        return app(FindOrCreateConversationAction::class)->execute(
            $number,
            $conversation->customer_phone,
            $conversation->customer_name,
        );
    }

    #[Computed]
    public function canSendFreeform(): bool
    {
        $target = $this->targetConversationForReply;

        return $target?->canSendFreeformReply() ?? false;
    }

    #[Computed]
    public function canSendMessages(): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        if (filament()->getCurrentPanel()?->getId() === 'admin') {
            return $user->can('whatsapp.platform.troubleshoot');
        }

        return $user->can('whatsapp.send_messages');
    }

    #[Computed]
    public function canSendTemplates(): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        if (filament()->getCurrentPanel()?->getId() === 'admin') {
            return $user->can('whatsapp.platform.troubleshoot');
        }

        return $user->can('whatsapp.send_template_messages');
    }
}
