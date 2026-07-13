<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessengerOnboardingSession extends Model
{
    protected $table = 'messenger_onboarding_sessions';

    protected $fillable = [
        'nonce',
        'tenant_id',
        'user_id',
        'status',
        'user_access_token',
        'pages_payload',
        'selected_page_ids',
        'connected_page_ids',
        'last_error',
        'return_url',
        'expires_at',
        'completed_at',
        'failed_at',
    ];

    protected $hidden = [
        'user_access_token',
        'pages_payload',
    ];

    protected function casts(): array
    {
        return [
            'user_access_token' => 'encrypted',
            'pages_payload' => 'encrypted:array',
            'selected_page_ids' => 'array',
            'connected_page_ids' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function markCompleted(): void
    {
        $this->completed_at = now();
        $this->failed_at = null;
        $this->user_access_token = null;
    }

    public function markFailed(): void
    {
        $this->failed_at = now();
        $this->completed_at = null;
        $this->user_access_token = null;
        $this->pages_payload = null;
    }

    /**
     * Safe page list for UI (no access tokens).
     *
     * @return list<array{page_id: string, page_name: ?string}>
     */
    public function safePagesForUi(): array
    {
        $pages = is_array($this->pages_payload) ? $this->pages_payload : [];
        $safe = [];

        foreach ($pages as $page) {
            if (! is_array($page) || blank($page['page_id'] ?? null)) {
                continue;
            }

            $safe[] = [
                'page_id' => (string) $page['page_id'],
                'page_name' => isset($page['page_name']) ? (string) $page['page_name'] : null,
            ];
        }

        return $safe;
    }
}
