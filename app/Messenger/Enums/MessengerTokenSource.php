<?php

namespace App\Messenger\Enums;

enum MessengerTokenSource: string
{
    case Manual = 'manual';
    case FacebookLogin = 'facebook_login';
    case Unknown = 'unknown';
}
