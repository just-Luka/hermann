<?php

declare(strict_types=1);

namespace App\Trait\Message\Formatter;

trait MessageFormatterTrait
{
    /**
     * Formats the value with a $ sign for negative/positive values.
     *
     * @param float $value
     * @return string
     */
    public function addDollarSign(float $value): string
    {
        if ($value < 0) {
            return '-$' . number_format(abs($value), 2);
        }
    
        return '$' . number_format($value, 2);
    }

    /**
     * Formats the liquidation price to a specified number of decimal places.
     *
     * @param float $liquidationPrice The price to format.
     * @param int $decimalPlaces The number of decimal places to include in the output.
     * @return string Formatted price or "0" if the price is too small.
     */
    public function formatLiqPrice(float $liquidationPrice, int $decimalPlaces): string
    {
        // Check if the price is smaller than the defined threshold
        return $liquidationPrice < 0.00000001 ? "0" : number_format($liquidationPrice, $decimalPlaces);
    }
    
    /**
     * formatOvernightPercent
     *
     * @param  float $overnightFeePercent
     * @return string
     */
    public function formatOvernightPercent(float $overnightFeePercent): string
    {
        return ($overnightFeePercent == 0) ? "<b>No</b>" : number_format($overnightFeePercent, 3) . '%';
    }

    /**
     * @param string $input
     * @return float|string
     */
    private function sanitizeFloatInput(string $input): float|string
    {
        $input = trim($input);
        $normalizedInput = str_replace(',', '.', $input);
        if (is_numeric($normalizedInput)) {
            return floatval($normalizedInput);
        }

        return $input;
    }
}
