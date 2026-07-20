<?php

namespace App\Support\MessagingHealth;

use App\Messenger\Enums\MessengerPageStatus;
use App\Models\MessengerPageRegistry;

/**
 * Deterministic Messenger registry health (central metadata only — no Graph calls).
 *
 * Rules (first match wins):
 * 1. status=failed → failed
 * 2. status=reconnect_required → reconnect_required
 * 3. !is_active OR status=disabled → disabled
 * 4. status=active AND is_active AND webhook subscribed → healthy
 * 5. status=active AND is_active AND webhook not subscribed → warning
 * 6. otherwise → unknown
 */
class MessengerRegistryHealthEvaluator
{
    public function evaluate(MessengerPageRegistry $registry): MessagingHealthStatus
    {
        $status = $registry->status;

        if ($status === MessengerPageStatus::Failed) {
            return MessagingHealthStatus::Failed;
        }

        if ($status === MessengerPageStatus::ReconnectRequired) {
            return MessagingHealthStatus::ReconnectRequired;
        }

        if (! $registry->is_active || $status === MessengerPageStatus::Disabled) {
            return MessagingHealthStatus::Disabled;
        }

        if ($status === MessengerPageStatus::Active && $registry->is_active) {
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
