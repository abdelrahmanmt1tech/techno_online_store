<?php

namespace App\Models\Tenant;

use App\Messenger\Enums\MessengerApiRequestOperation;
use App\Messenger\Enums\MessengerApiRequestOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerApiRequest extends Model
{
    protected $connection = 'tenant';

    protected $table = 'messenger_api_requests';

    protected $fillable = [
        'messenger_page_id',
        'messenger_message_id',
        'operation',
        'recipient_psid',
        'http_status',
        'api_error_code',
        'outcome',
        'status_label',
        'summary',
        'request_payload',
        'response_body',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'operation' => MessengerApiRequestOperation::class,
            'outcome' => MessengerApiRequestOutcome::class,
            'request_payload' => 'array',
            'response_body' => 'array',
        ];
    }

    public function messengerPage(): BelongsTo
    {
        return $this->belongsTo(MessengerPage::class);
    }

    public function messengerMessage(): BelongsTo
    {
        return $this->belongsTo(MessengerMessage::class);
    }
}
