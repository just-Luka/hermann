<?php

namespace App\Enum\TradingBot\Question;

enum DepositQuestion: string
{
    case CHOOSING_DEPOSIT = 'CHOOSING_DEPOSIT';
    case TYPING_USD_AMOUNT = 'TYPING_USD_AMOUNT';
}
