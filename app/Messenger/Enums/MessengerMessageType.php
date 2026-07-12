<?php

namespace App\Messenger\Enums;

enum MessengerMessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';
    case File = 'file';
    case Postback = 'postback';
    case Other = 'other';
}
