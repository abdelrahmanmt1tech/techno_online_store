<?php

namespace App\Messenger\Enums;

enum MessengerMessageSenderType: string
{
    case Customer = 'customer';
    case Agent = 'agent';
    case System = 'system';
    case Page = 'page';
}
