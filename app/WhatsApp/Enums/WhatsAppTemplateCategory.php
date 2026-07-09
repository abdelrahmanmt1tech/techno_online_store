<?php

namespace App\WhatsApp\Enums;

enum WhatsAppTemplateCategory: string
{
    case Marketing = 'marketing';
    case Utility = 'utility';
    case Authentication = 'authentication';
}
