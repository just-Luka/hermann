<?php

declare(strict_types=1);

namespace App\Trait\Message;

use App\Trait\CalculationTrait;
use App\Trait\Message\Formatter\MessageFormatterTrait;

trait OpenMessageTrait
{
    use CalculationTrait, MessageFormatterTrait;

    /**
     * searchMessage
     *
     * @param  array $instructions
     * @return string
     */
    public function searchMessage(array $instructions): string
    {
        $pairsCount = count($instructions['assets']);

        $message = "Found $pairsCount assets:\n\n";
        foreach ($instructions['assets'] as $index => $pair) {
            $number = $index + 1;
            $marketType = $pair['instrumentType'];
            $instrumentName = $pair['instrumentName'];
            $askPrice = $pair['offer'];
            $bidPrice = $pair['bid'];
            
            $message .= "$number. <b>$instrumentName</b> ($marketType)\n";
            $message .= "---------------------------------------\n";
            $message .= "Ask price: <b>$askPrice</b>\n";
            $message .= "Bid price: <b>$bidPrice</b>\n";
            $message .= "---------------------------------------\n\n";
        }

        $message .= $pairsCount > 0 ? "Please choose a <b>number</b>\n\nor type /exit to cancel" : "Please try again\n\nor type /exit to cancel";

        return $message;
    }
    
    /**
     * createMessage
     *
     * @param  array $pair
     * @param  float $balance
     * @return string
     */
    public function createMessage(array $pair, float $balance): string
    {
        $instrumentName = $pair['instrument']['name'];
        $symbol = $pair['instrument']['symbol'];
        $overnightFeeLong = $this->overnightFee($pair['instrument']['overnightFee']['longRate']);
        $overnightFeeShort = $this->overnightFee($pair['instrument']['overnightFee']['shortRate']);
        $minDealSize = $pair['dealingRules']['minDealSize']['value'];
        $maxDealSize = $pair['dealingRules']['maxDealSize']['value'];
        $askPrice = $pair['snapshot']['offer'];
        $bidPrice = $pair['snapshot']['bid'];
        $leverage = $pair['instrument']['leverage'];
        $minSizeIncrementValue = $pair['dealingRules']['minSizeIncrement']['value'];

        $minAvailableForLong = $this->minAssetSizeForUser($balance, $askPrice, $leverage, $minDealSize);
        $maxAvailableForLong = $this->maxAssetSizeForUser($balance, $askPrice, $leverage, $minSizeIncrementValue);

        $overnightFeeLongFormatted = $this->formatOvernightPercent($overnightFeeLong);
        $overnightFeeShortFormatted = $this->formatOvernightPercent($overnightFeeShort);

        $message = 
        "
<b>$instrumentName</b>
<b>($symbol)</b>

---------------------------------------
Ask Price: $askPrice
Bid Price: $bidPrice
---------------------------------------
Overnight Fee (Long): $overnightFeeLongFormatted
Overnight Fee (Short): $overnightFeeShortFormatted
---------------------------------------
Deal Size (Min): $minDealSize
Deal Size (Max): $maxDealSize
---------------------------------------
Leverage: $leverage:1

==========================
Balance: <b>$$balance</b>
Available (Min): $minAvailableForLong
Available (Max): $maxAvailableForLong
==========================

Please type the <b>amount</b> you want to buy/sell

Type /exit to cancel
        ";

        return $message;
    }

    public function amountMessage(float $userBalance, float $amount, array $pair): string
    {
        $instrumentName = $pair['instrument']['name'];
        $symbol = $pair['instrument']['symbol'];
        $askPrice = $pair['snapshot']['offer'];
        $bidPrice = $pair['snapshot']['bid'];
        $decimalPlaces = $pair['snapshot']['decimalPlacesFactor'];
        $overnightFeeLong = $pair['instrument']['overnightFee']['longRate'];
        $overnightFeeShort = $pair['instrument']['overnightFee']['shortRate'];
        $leverage = $pair['instrument']['leverage'];
        $cost = $askPrice * $amount / $leverage;
    
        // Adjusted overnight fees
        $overnightFeeLongAdjusted = $this->addDollarSign($this->overnightFeeWithAmount($amount, $askPrice, $overnightFeeLong));
        $overnightFeeShortAdjusted = $this->addDollarSign($this->overnightFeeWithAmount($amount, $askPrice, $overnightFeeShort));
    
        // Liquidation price based on margin and balance
        $liqPriceLong = $this->liquidationPrice($userBalance, $amount, $askPrice, $leverage, true);
        $liqPriceShort = $this->liquidationPrice($userBalance, $amount, $askPrice, $leverage, false);
    
        $liqPriceLongFormatted = $this->formatLiqPrice($liqPriceLong, $decimalPlaces);
        $liqPriceShortFormatted = $this->formatLiqPrice($liqPriceShort, $decimalPlaces);

        $message = 
"
<b>$instrumentName</b>
<b>($symbol)</b>

---------------------------------------
Cost(Margin): <b>$$cost</b>
Size: $amount
Leverage: $leverage:1
---------------------------------------
Ask Price: $askPrice
Bid Price: $bidPrice
---------------------------------------
Overnight Fee (Long): $overnightFeeLongAdjusted
Overnight Fee (Short): $overnightFeeShortAdjusted
---------------------------------------
Estimated Liq Price (Long): $liqPriceLongFormatted
Estimated Liq Price (Short): $liqPriceShortFormatted

Please type <b>BUY</b> or <b>SELL</b>
to confirm transaction


Type /exit to cancel
";

        return $message;
    }

    public function buyMessage(array $confirmation, float $userBalance): string
    {
        $liqPriceLong = $this->liquidationPrice($userBalance, $confirmation['size'], $confirmation['level'], $confirmation['leverage']);
        
        $liqPriceLongFormatted = $this->formatLiqPrice($liqPriceLong, $confirmation['decimalPlaces']);
        $userBalanceFormatted = number_format($userBalance, 2);
        $message = "
Your order was accepted ✅
---------------------------------------
<b>{$confirmation['epic']}</b>
Entry price: {$confirmation['level']}
Size: {$confirmation['size']}
Direction: BUY
DR code: {$confirmation['dealReference']}

---------------------------------------
Balance: $$userBalanceFormatted
Margin Balance: $100
Estimated Liq. Price: $liqPriceLongFormatted
";

        return $message;
    }

    public function sellMessage(array $confirmation, float $userBalance): string
    {
        $liqPriceLong = $this->liquidationPrice($userBalance, $confirmation['size'], $confirmation['level'], $confirmation['leverage'], false);
        
        $liqPriceLongFormatted = $this->formatLiqPrice($liqPriceLong, $confirmation['decimalPlaces']);
        $userBalanceFormatted = number_format($userBalance, 2);
        $message = "
Your order was accepted ✅
---------------------------------------
<b>{$confirmation['epic']}</b>
Entry price: {$confirmation['level']}
Size: {$confirmation['size']}
Direction: SELL
DR code: {$confirmation['dealReference']}

---------------------------------------
Balance: $$userBalanceFormatted
Margin Balance: $100
Estimated Liq. Price: $liqPriceLongFormatted
";

        return $message;
    }
}
