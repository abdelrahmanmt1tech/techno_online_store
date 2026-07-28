<?php

namespace App\Enums\Pos;

enum CashierSessionStatus: string
{
    case Opening = 'opening';
    case Opened = 'opened';
    case Closing = 'closing';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('commerce.session_statuses.'.$this->value);
    }

    public function isOperational(): bool
    {
        return $this === self::Opened;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled], true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Opening => [self::Opened, self::Cancelled],
            self::Opened => [self::Closing, self::Cancelled],
            self::Closing => [self::Closed, self::Opened],
            self::Closed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
