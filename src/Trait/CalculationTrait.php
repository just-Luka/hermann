<?php

declare(strict_types=1);

namespace App\Trait;

trait CalculationTrait
{    
    /**
     * overnightFee
     * Calculates overnight fee for a pair
     * 
     * @param  float $rate
     * @return float
     */
    public function overnightFee(float $rate): float
    {
        $feePercentage = (float)$_ENV['OVERNIGHT_FEE_PERCENT'];
        $overnightPositiveFeeDisabled = filter_var($_ENV['OVERNIGHT_POSITIVE_FEE_DISABLED'], FILTER_VALIDATE_BOOLEAN);

        if ($overnightPositiveFeeDisabled && $rate > 0) {
            return 0.0;
        }

        // Apply fee adjustment
        $adjustedRate = $rate * ($rate > 0 ? (1 - $feePercentage / 100) : (1 + $feePercentage / 100));

        // Round with custom logic
        return $this->customRound($adjustedRate);
    }
    
    /**
     * maxAssetSizeForUser
     * Calculates how much asset user can afford buying based on his current balance
     *
     * @param  float $userBalance
     * @param  float $askPrice
     * @param  int $leverage
     * @param  float $minSizeIncrementValue
     * @return float
     */
    public function maxAssetSizeForUser(float $userBalance, float $askPrice, int $leverage, float $minSizeIncrementValue): float
    {
        $maxSize = ($userBalance * $leverage) / $askPrice;
        return floor($maxSize / $minSizeIncrementValue) * $minSizeIncrementValue;
    }
    
    /**
     * minAssetSizeForUser
     *
     * @param  float $userBalance
     * @param  float $askPrice
     * @param  int $leverage
     * @param  float $minDealSize
     * @return float
     */
    public function minAssetSizeForUser(float $userBalance, float $askPrice, int $leverage, float $minDealSize): float
    {
        return min($minDealSize, ($userBalance * $leverage) / $askPrice);
    }

    /**
     * Calculates the overnight fee for a given amount and ask price with custom rounding logic.
     *
     * @param  float $amount
     * @param  float $askPrice
     * @param  float $rate
     * @return float
    */
    public function overnightFeeWithAmount(float $amount, float $askPrice, float $rate)
    {
        $feePercentage = (float) $_ENV['OVERNIGHT_FEE_PERCENT'];
        $overnightPositiveFeeDisabled = filter_var($_ENV['OVERNIGHT_POSITIVE_FEE_DISABLED'], FILTER_VALIDATE_BOOLEAN);

        $fee = ($askPrice * $amount) * ($rate / 100);

        if ($overnightPositiveFeeDisabled && $rate > 0) {
            return 0.0;
        }
        
        if ($rate > 0) {
            $adjustedFee = $fee * (1 - $feePercentage / 100); // Decrease positive fee by feePercentage%
        } else {
            $adjustedFee = $fee * (1 + $feePercentage / 100); // Increase negative fee by feePercentage%
        }

        return $this->customRound($adjustedFee, 2);
    }

    /**
     * Calculates the liquidation price for a trading position based on the user's balance, position size, ask price, leverage, and position type (long or short).
     *
     * @param float $userBalance The current balance of the user.
     * @param float $size The size/amount of the position.
     * @param float $askPrice The current ask price of the asset.
     * @param int $leverage The leverage used for the position.
     * @param bool $forLong Indicates whether the position is a long position (default is true).
     * @return float The calculated liquidation price for the position.
     */
    public function liquidationPrice(float $userBalance, float $size, float $askPrice, int $leverage, bool $forLong = true): float
    {
        $positionValue = $askPrice * $size;
        $initialMargin = $userBalance / $leverage;
        $securingMargin = 0.9; // with 10% margin position will be closed (Maybe set in .env)
        
        if ($forLong) { // LONG
            return ($positionValue - ($userBalance * $securingMargin - $initialMargin)) / $size;
        } else { // SHORT
            return ($positionValue + ($userBalance * $securingMargin - $initialMargin)) / $size;
        }
    }
    
    /**
     * Calculates new balance, based on current balance, new value and value decimals
     *
     * @param  float $currentBalance
     * @param  int $value 
     * @param  int $decimals
     * @return float
     */
    public function addUSDBalance(float $currentBalance, int $value, int $decimals): float
    {   
        return $currentBalance + ($value / (10 ** $decimals));
    }

    /**
     * Calculates new balance, based on current balance, new value and value decimals
     *
     * @param  float $currentBalance
     * @param  int $value 
     * @param  int $decimals
     * @return float
     */
    public function minusUSDBalance(float $currentBalance, int $value, int $decimals): float
    {   
        return $currentBalance - ($value / (10 ** $decimals));
    }


    /**
     * Applies custom rounding logic: negative values round away from zero, positive towards zero.
     *
     * @param  float $value
     * @param  int $precision
     * @return float
     */
    private function customRound(float $value, int $precision = 3): float
    {
        $factor = pow(10, $precision);
        return $value < 0 ? ceil($value * $factor) / $factor : floor($value * $factor) / $factor;
    }
}
