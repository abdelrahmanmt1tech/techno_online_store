<?php

namespace App\Messenger\Onboarding;

use App\Messenger\Enums\MessengerConnectionMethod;
use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Enums\MessengerTokenSource;
use App\Messenger\Services\MessengerTenantContextService;
use App\Models\MessengerOnboardingSession;
use App\Models\Tenant;
use App\Models\Tenant\MessengerPage;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ConnectSelectedMessengerPagesAction
{
    public function __construct(
        protected SubscribeMessengerPageWebhooksAction $subscribeAction,
        protected MessengerTenantContextService $tenantContext,
    ) {}

    /**
     * @param  list<string>  $selectedPageIds
     */
    public function execute(MessengerOnboardingState $state, array $selectedPageIds): MessengerOnboardingSession
    {
        $session = MessengerOnboardingSession::query()->where('nonce', $state->nonce)->first();

        if ($session === null) {
            throw new RuntimeException('Messenger onboarding session was not found.');
        }

        if ($session->tenant_id !== $state->tenantId) {
            throw new RuntimeException('Onboarding session tenant does not match signed state.');
        }

        if (! in_array($session->status, ['awaiting_page_selection', 'failed', 'subscribing_webhooks'], true)) {
            throw new RuntimeException('Onboarding session is not ready for page connection.');
        }

        $selectedPageIds = array_values(array_unique(array_filter(array_map('strval', $selectedPageIds))));

        if ($selectedPageIds === []) {
            throw new RuntimeException('Select at least one Facebook Page to connect.');
        }

        $pagesPayload = is_array($session->pages_payload) ? $session->pages_payload : [];
        $pagesById = collect($pagesPayload)->keyBy(fn ($page) => (string) ($page['page_id'] ?? ''));

        foreach ($selectedPageIds as $pageId) {
            if (! $pagesById->has($pageId)) {
                throw new RuntimeException('One or more selected pages are not available in this onboarding session.');
            }
        }

        $tenant = Tenant::query()->find($state->tenantId);

        if ($tenant === null) {
            $session->status = 'failed';
            $session->last_error = 'Onboarding tenant was not found.';
            $session->markFailed();
            $session->save();

            return $session->fresh();
        }

        $session->status = 'subscribing_webhooks';
        $session->selected_page_ids = $selectedPageIds;
        $session->last_error = null;
        $session->failed_at = null;
        $session->save();

        $connected = [];
        $errors = [];

        try {
            $this->tenantContext->runForTenant($tenant, function () use ($selectedPageIds, $pagesById, &$connected, &$errors) {
                foreach ($selectedPageIds as $pageId) {
                    $meta = $pagesById->get($pageId);
                    $token = (string) ($meta['page_access_token'] ?? '');
                    $name = isset($meta['page_name']) ? (string) $meta['page_name'] : null;

                    if ($token === '') {
                        $errors[] = "Page {$pageId} is missing an access token.";

                        continue;
                    }

                    $page = MessengerPage::query()->updateOrCreate(
                        ['page_id' => $pageId],
                        [
                            'page_name' => $name ?: $pageId,
                            'page_access_token' => $token,
                            'token_source' => MessengerTokenSource::FacebookLogin,
                            'connection_method' => MessengerConnectionMethod::FacebookLogin,
                            'status' => MessengerPageStatus::Active,
                            'is_active' => true,
                            'last_error_message' => null,
                            'reconnect_required_at' => null,
                            'connected_at' => now(),
                            'disconnected_at' => null,
                            'webhook_status' => 'pending',
                        ],
                    );

                    try {
                        $this->subscribeAction->execute($page);
                        $page->forceFill([
                            'status' => MessengerPageStatus::Active,
                            'is_active' => true,
                            'webhook_status' => 'subscribed',
                            'last_error_message' => null,
                        ])->save();
                        $connected[] = $pageId;
                    } catch (Throwable $exception) {
                        $page->forceFill([
                            'status' => MessengerPageStatus::ReconnectRequired,
                            'webhook_status' => 'failed',
                            'last_error_message' => $exception->getMessage(),
                            'reconnect_required_at' => now(),
                        ])->save();
                        $errors[] = $exception->getMessage();
                    }
                }

                $this->ensureDefaultPage();
            });
        } finally {
            $this->tenantContext->end();
        }

        $session->connected_page_ids = $connected;
        $session->pages_payload = null;
        $session->user_access_token = null;

        if ($connected === [] && $errors !== []) {
            $session->status = 'failed';
            $session->last_error = implode(' | ', array_slice($errors, 0, 3));
            $session->markFailed();
            $session->save();

            return $session->fresh();
        }

        if ($errors !== []) {
            $session->status = 'completed_with_errors';
            $session->last_error = implode(' | ', array_slice($errors, 0, 3));
        } else {
            $session->status = 'completed';
            $session->last_error = null;
        }

        $session->markCompleted();
        $session->save();

        Log::channel(config('messenger.log_channel'))->info('Messenger Facebook Login pages connected', [
            'tenant_id' => $session->tenant_id,
            'nonce' => $session->nonce,
            'connected_count' => count($connected),
            'error_count' => count($errors),
        ]);

        return $session->fresh();
    }

    /**
     * Retry webhook subscription for an existing Facebook Login page.
     */
    public function retrySubscription(Tenant $tenant, MessengerPage $page): MessengerPage
    {
        return $this->tenantContext->runForTenant($tenant, function () use ($page) {
            $fresh = MessengerPage::query()->findOrFail($page->id);

            try {
                $this->subscribeAction->execute($fresh);
                $fresh->forceFill([
                    'status' => MessengerPageStatus::Active,
                    'is_active' => true,
                    'webhook_status' => 'subscribed',
                    'last_error_message' => null,
                    'reconnect_required_at' => null,
                ])->save();
            } catch (Throwable $exception) {
                $fresh->forceFill([
                    'status' => MessengerPageStatus::ReconnectRequired,
                    'webhook_status' => 'failed',
                    'last_error_message' => $exception->getMessage(),
                    'reconnect_required_at' => now(),
                ])->save();
            }

            return $fresh->fresh();
        });
    }

    protected function ensureDefaultPage(): void
    {
        if (MessengerPage::query()->where('is_default', true)->exists()) {
            return;
        }

        $first = MessengerPage::query()->where('is_active', true)->orderBy('id')->first();

        if ($first !== null) {
            $first->forceFill(['is_default' => true])->save();
        }
    }
}
