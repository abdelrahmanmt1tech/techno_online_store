<?php

namespace App\Support\MessagingHealth;

enum MessagingHealthStatus: string
{
    case Healthy = 'healthy';
    case Warning = 'warning';
    case ReconnectRequired = 'reconnect_required';
    case Failed = 'failed';
    case Disabled = 'disabled';
    case Pending = 'pending';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Healthy => __('dashboard.messaging_health_state_healthy'),
            self::Warning => __('dashboard.messaging_health_state_warning'),
            self::ReconnectRequired => __('dashboard.messaging_health_state_reconnect_required'),
            self::Failed => __('dashboard.messaging_health_state_failed'),
            self::Disabled => __('dashboard.messaging_health_state_disabled'),
            self::Pending => __('dashboard.messaging_health_state_pending'),
            self::Unknown => __('dashboard.messaging_health_state_unknown'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Healthy => 'success',
            self::Warning => 'warning',
            self::ReconnectRequired => 'danger',
            self::Failed => 'danger',
            self::Disabled => 'gray',
            self::Pending => 'info',
            self::Unknown => 'gray',
        };
    }

    public function needsAttention(): bool
    {
        return in_array($this, [
            self::Warning,
            self::ReconnectRequired,
            self::Failed,
            self::Pending,
            self::Unknown,
        ], true);
    }
}
