<?php

namespace App\Enums\Pos;

enum ReceiptNumberStrategy: string
{
    case PerRegister = 'per_register';
    case Global = 'global';
}
