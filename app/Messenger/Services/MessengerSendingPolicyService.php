<?php

namespace App\Messenger\Services;

use App\Messenger\DTOs\MessengerSendingPolicyResult;
use App\Messenger\Enums\MessengerPageStatus;
use App\Models\Tenant\MessengerConversation;
use App\Models\Tenant\MessengerPage;

class MessengerSendingPolicyService
{
    public function canSendText(
        MessengerPage $page,
        MessengerConversation $conversation,
    ): MessengerSendingPolicyResult {
        if (! $page->is_active || $page->status !== MessengerPageStatus::Active) {
            return MessengerSendingPolicyResult::deny(
                'Messenger page is inactive or unavailable for sending.',
            );
        }

        if (blank($page->page_access_token)) {
            return MessengerSendingPolicyResult::deny(
                'Messenger page access token is missing.',
            );
        }

        if ((int) $conversation->messenger_page_id !== (int) $page->id) {
            return MessengerSendingPolicyResult::deny(
                'Conversation does not belong to this Messenger page.',
            );
        }

        if (! $conversation->canSendFreeformReply()) {
            return MessengerSendingPolicyResult::deny(
                'Customer service window is closed. Freeform Messenger replies are only allowed within 24 hours of the last customer message.',
            );
        }

        return MessengerSendingPolicyResult::allow($conversation);
    }
}
