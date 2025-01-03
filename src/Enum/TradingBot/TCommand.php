<?php

namespace App\Enum\TradingBot;

enum TCommand: string
{
    case START = 'start';
    case ME = 'me';
    case OPEN = 'open';
    case DEPOSIT = 'deposit';
    case EXIT = 'exit';
}
