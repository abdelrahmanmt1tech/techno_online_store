<?php

namespace App\WhatsApp\Enums;

enum WhatsAppMessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';
    case Location = 'location';
    case Interactive = 'interactive';
    case Template = 'template';
    case Unsupported = 'unsupported';
}
