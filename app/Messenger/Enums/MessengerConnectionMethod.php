<?php

namespace App\Messenger\Enums;

enum MessengerConnectionMethod: string
{
    case Manual = 'manual';
    case FacebookLogin = 'facebook_login';
}
