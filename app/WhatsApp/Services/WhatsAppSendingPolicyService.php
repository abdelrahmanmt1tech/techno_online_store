<?php

namespace App\WhatsApp\Services;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;
use App\WhatsApp\DTOs\SendingPolicyResult;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppTemplateStatus;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppSendingPolicyService
{
    public function __construct(
        protected WhatsAppCloudApiService $cloudApi,
    ) {}

    public function canSendText(
        ?Authenticatable $user,
        WhatsAppNumber $number,
        WhatsAppConversation $conversation,
        string $guard = 'tenant',
    ): SendingPolicyResult {
        $base = $this->validateBase($user, $number, $conversation, $guard, 'send_messages');

        if (! $base->allowed) {
            return $base;
        }

        if (! $conversation->canSendFreeformReply()) {
            return SendingPolicyResult::deny(
                __('dashboard.whatsapp_window_closed_message'),
                mustUseTemplate: true,
            );
        }

        return SendingPolicyResult::allow($conversation);
    }

    public function canSendTemplate(
        ?Authenticatable $user,
        WhatsAppNumber $number,
        WhatsAppConversation $conversation,
        WhatsAppTemplate $template,
        string $guard = 'tenant',
    ): SendingPolicyResult {
        $permission = $guard === 'admin' ? 'send_test_messages' : 'send_template_messages';
        $base = $this->validateBase($user, $number, $conversation, $guard, $permission);

        if (! $base->allowed) {
            return $base;
        }

        if ($template->status !== WhatsAppTemplateStatus::Approved || $template->is_disabled_locally) {
            return SendingPolicyResult::deny(__('dashboard.whatsapp_template_not_approved'));
        }

        // Future: enforce customer opt-in before proactive/campaign template sends.
        return SendingPolicyResult::allow($conversation);
    }

    public function resolveConversationForNumber(
        WhatsAppNumber $number,
        string $customerPhone,
        ?string $customerName = null,
    ): WhatsAppConversation {
        return WhatsAppConversation::query()->firstOrCreate(
            [
                'whatsapp_number_id' => $number->id,
                'customer_phone' => $this->cloudApi->normalizePhone($customerPhone),
            ],
            [
                'customer_name' => $customerName,
            ],
        );
    }

    protected function validateBase(
        ?Authenticatable $user,
        WhatsAppNumber $number,
        WhatsAppConversation $conversation,
        string $guard,
        string $permission,
    ): SendingPolicyResult {
        if ($user === null) {
            return SendingPolicyResult::deny(__('dashboard.whatsapp_unauthenticated'));
        }

        $permissionKey = $this->permissionKey($guard, $permission);

        if (! $user->can($permissionKey)) {
            return SendingPolicyResult::deny(__('dashboard.whatsapp_permission_denied'));
        }

        if (! $number->is_active || $number->status !== WhatsAppConnectionStatus::Active) {
            return SendingPolicyResult::deny(__('dashboard.whatsapp_number_inactive'));
        }

        if ($conversation->whatsapp_number_id !== $number->id) {
            return SendingPolicyResult::deny(__('dashboard.whatsapp_conversation_number_mismatch'));
        }

        $tenantKey = tenant()?->getTenantKey();
        $rateLimitKey = 'whatsapp-send:'.($tenantKey ?? 'central').':'.$user->getAuthIdentifier();

        if (RateLimiter::tooManyAttempts($rateLimitKey, config('whatsapp.send_rate_limit', 30))) {
            return SendingPolicyResult::deny(__('dashboard.whatsapp_rate_limited'));
        }

        RateLimiter::hit($rateLimitKey, 60);

        return SendingPolicyResult::allow($conversation);
    }

    protected function permissionKey(string $guard, string $action): string
    {
        if ($guard === 'admin') {
            return match ($action) {
                'send_messages', 'send_template_messages', 'send_test_messages' => 'whatsapp.platform.troubleshoot',
                default => 'whatsapp.platform.'.$action,
            };
        }

        return 'whatsapp.'.$action;
    }
}
