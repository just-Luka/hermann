<?php

declare(strict_types=1);

namespace App\Channel\Signals\Template;

final class TradingSignalTemplate
{
    public static function default(): string
    {
        return "
🚨 Trading Signal 🚨
__________________________________
Asset: %s - <b>%s</b>
Entry Point: <b>%s</b>
Take Profit: <b>%s</b>
Stop Loss: <b>%s</b>
Timeframe: <b>%s</b>
Trade Type: <b>%s</b>
———————————————
🔍 Risk Management:
Position Size: <b>%s%%</b> of your balance
Probability of Success: <b>%s%%</b>

⚠️ Note: Signals are for informational purposes only. Trading involves risk; manage your trades responsibly.
        ";
    }
}