<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Command;

class PrimaryBC 
{
    public static function start(): string
    {
        return "
Welcome! Here are our Telegram channels:
Channel 1: https://t.me/your_channel_1
Channel 2: https://t.me/your_channel_2
";
    }
}
