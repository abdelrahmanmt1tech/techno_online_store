<?php

namespace App\Messenger\Enums;

enum MessengerPageStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case ReconnectRequired = 'reconnect_required';
    case Failed = 'failed';
}
