<?php

namespace App\Support\MessagingHealth;

use App\Models\WhatsAppNumberRegistry;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;

/**
 * Deterministic WhatsApp registry health (central metadata only — no Graph calls).
 *
 * Rules (first match wins):
 * 1. status=failed → failed
 * 2. status=reconnect_required → reconnect_required
 * 3. !is_active OR status=disabled → disabled
 * 4. onboarding not completed (and not disconnected) → pending
 * 5. status=active AND is_active AND webhook subscribed → healthy
 * 6. status=active AND is_active AND webhook not subscribed → warning
 * 7. otherwise → unknown
 */
class WhatsAppRegistryHealthEvaluator
{
    public function evaluate(WhatsAppNumberRegistry $registry): MessagingHealthStatus
    {
        $status = $registry->status;

        if ($status === WhatsAppConnectionStatus::Failed) {
            return MessagingHealthStatus::Failed;
        }

        if ($status === WhatsAppConnectionStatus::ReconnectRequired) {
            return MessagingHealthStatus::ReconnectRequired;
        }

        if (! $registry->is_active || $status === WhatsAppConnectionStatus::Disabled) {
            return MessagingHealthStatus::Disabled;
        }

        $onboarding = $registry->onboarding_status;

        if ($onboarding instanceof WhatsAppOnboardingStatus
            && ! in_array($onboarding, [
                WhatsAppOnboardingStatus::Completed,
                WhatsAppOnboardingStatus::Disconnected,
                WhatsAppOnboardingStatus::NotStarted,
            ], true)
        ) {
            return MessagingHealthStatus::Pending;
        }

        if ($onboarding === WhatsAppOnboardingStatus::Failed) {
            return MessagingHealthStatus::Failed;
        }

        if ($status === WhatsAppConnectionStatus::Active && $registry->is_active) {
            if ($this->isWebhookSubscribed($registry->webhook_status)) {
                return MessagingHealthStatus::Healthy;
            }

            return MessagingHealthStatus::Warning;
        }

        return MessagingHealthStatus::Unknown;
    }

    protected function isWebhookSubscribed(?string $webhookStatus): bool
    {
        return strtolower((string) $webhookStatus) === 'subscribed';
    }
}
