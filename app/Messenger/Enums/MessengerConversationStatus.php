<?php

namespace App\Messenger\Enums;

enum MessengerConversationStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Closed = 'closed';
}
