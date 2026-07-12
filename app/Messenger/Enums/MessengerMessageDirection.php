<?php

namespace App\Messenger\Enums;

enum MessengerMessageDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
