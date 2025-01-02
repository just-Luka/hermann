<?php

namespace App\Enum\TradingBot\Question;

enum OpenQuestion: string
{
    case SEARCH_ASSET = 'SEARCH_ASSET';
    case CHOOSING_ASSET = 'CHOOSING_ASSET';
    case TYPING_AMOUNT = 'TYPING_AMOUNT';
    case CONFIRMING_AMOUNT = 'CONFIRMING_AMOUNT';
}
