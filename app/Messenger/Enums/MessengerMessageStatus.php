<?php

namespace App\Messenger\Enums;

enum MessengerMessageStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Received = 'received';

    public function rank(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::Failed => 1,
            self::Sent => 2,
            self::Received => 3,
            self::Delivered => 4,
            self::Read => 5,
        };
    }

    public function canTransitionTo(self $next): bool
    {
        if ($this === self::Failed || $next === self::Failed) {
            return true;
        }

        return $next->rank() >= $this->rank();
    }
}
